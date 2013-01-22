<?php
//产品库逻辑
class mybuy extends top{

	function __construct(){
		  parent::__construct(); 
		  require_once("init/Extensions/saetv2.ex.class.php");
		  set_time_limit(0);
	}	
	
	function mybuy_admin() {
		if($_SESSION['admin'] != 1){
            prient_jump(spUrl('main'));
        }
		//是否还有access_token
	
		if(!$this->check_access()){
			$aurl = $this->o->getAuthorizeURL( WB_CALLBACK_URL );
			echo "<a href=$aurl>授权失效，点击授权</a>";
		}else{
			$this->show_adminList();
		}		
	}
	
	function callback(){
		if (isset($_REQUEST['code'])) {
			$this->returnWBobj();
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] = WB_CALLBACK_URL;
			$token = $this->o->getAccessToken( 'code', $keys ) ;				
			
		}
		file_put_contents('tmp/t.inc',$token['access_token']);		
		$this->show_adminList();
	}
	
	function show_adminList(){
		$db = spClass('db_mybuy');
		$rs = $db->spPager($this->spArgs('page', 1), 30)->findAll("status=0",'id desc');
		 if($this->spArgs('submit')){
            $this->pager = $db->spPager()->pagerHtml('mybuy', 'show_adminList', array('title' => $title, 'niname' => $niname, 'submit' => $this->spArgs('submit')));
        }else{
            $this->pager = $db->spPager()->pagerHtml('mybuy', 'show_adminList');
        }
		
		$this->mybuy = $rs;
		//print_r($this->mybuy);
		$this->display("admin/mybuy.html");
	}
	
	function agree(){
		$id = $this->spArgs("id");
		$db=spClass("db_mybuy");
		$rs = $db->update(array("id"=>$id),array("status"=>1));
		$rs = $db->find(array("id"=>$id));
		$this->notice($rs['weibonick'],$rs['weiboid']);
		$this->show_adminList();
	}
	
	function del(){
		$id = $this->spArgs("id");
		$db=spClass("db_mybuy");
		$db->update(array("id"=>$id),array("status"=>-1));
		$this->show_adminList();
	}
	
	function check_access(){		
		$checkUrl = "https://api.weibo.com/oauth2/get_token_info";
		$p['access_token']=$this->get_accesstoken();		
		$result = SaeTOAuthV2::oAuthRequest($checkUrl,"POST",$p);
		$rs = json_decode($result);	
		if(isset($rs->error)){
			$this->returnWBobj();
			return false;
		}else{
			return true;
		}	
	}
	
	function get_accesstoken(){
		return file_get_contents('tmp/t.inc');
	}
	
	function returnWBobj(){
			require_once("init/Extensions/saetv2.ex.class.php");
			define( "WB_AKEY" , '3006371478' );
			define( "WB_SKEY" , 'aa834a7e199a288d0faa88286da01b13' );
			define( "WB_CALLBACK_URL" , 'http://www.zplaying.com/index.php?c=mybuy&a=callback' );
			$this->o = new SaeTOAuthV2( WB_AKEY , WB_SKEY);
	}
	
	function ATme($page=1,$since_id="0"){
		//这里还缺超时判断
		$p['page']=$page;
		$p['access_token']=$this->get_accesstoken();
		$p['count']=100;
		$p['since_id']=$since_id;
		$url="https://api.weibo.com/2/statuses/mentions.json";
		$result = SaeTOAuthV2::oAuthRequest($url,"GET",$p);
		$rs = json_decode($result);	
		//print_r($rs);exit;
		
		foreach($rs->statuses as $k=>$r){
			if($r->original_pic){
				$d[$k]['pic']=$r->original_pic;
				$d[$k]['text']=$r->text;
				$d[$k]['weiboid']=(string)$r->idstr;
				$d[$k]['weibonick']=$r->user->name;
			}else{
				if($r->retweeted_status->original_pic){
					$d[$k]['pic']=$r->retweeted_status->original_pic;
					$d[$k]['text']=$r->retweeted_status->text;
					$d[$k]['weiboid']=(string)$r->retweeted_status->idstr;
					$d[$k]['weibonick']=$r->retweeted_status->user->name;
				}				
			}

			$d[$k]['url']=$r->user->domain?"http://weibo.com/u/".$r->user->domain:"http://weibo.com/u/".$r->user->id;
			
		}

		//print_r($d);exit;
		return $d;
		
	}

	function getAt(){
		if(!$this->check_access()){
			echo "access error,out of time";
			return;
		}
		$since_id = $this->returnSinceid()?$this->returnSinceid():"3533646079859847";
		//$since_id ='3533762572679178';
		//测试最大分页
		$page=1;
		do{
			$t = $this->ATme($page,$since_id);
			if(!empty($t)){
				$tmp[$page]=$t;
				$page++;
			}
			sleep(1);
			//print_r($tmp);exit;
		}while(!empty($t));
		//存储
		
		$realPage = $page-1;
	//echo "get maxpage:$realPage ok";
		$db = spClass('db_mybuy');
		do{
			//print_r($tmp[$realPage]);exit;
			arsort($tmp[$realPage]);
			foreach ($tmp[$realPage] as $k => $v) {
				if(!$db->find(array('weiboid'=>$v['weiboid']))&&!empty($v['pic']))
				$rs = $db->create($v);
			}	
			//确定是否有非重复数据				
			$realPage--;
		}while($realPage>1);
	}
	
	//通知用户
	function notice($weibonick,$id){
		$p['comment']="正玩已记录，这里可以看到您玩过的历史：http://www.zplaying.com/mybuy/show/n/".urlencode($weibonick);
		$p['access_token']=$this->get_accesstoken();
		$p['id']=$id;
		$url="https://api.weibo.com/2/comments/create.json";
		$result = SaeTOAuthV2::oAuthRequest($url,"POST",$p);
		$rs = json_decode($result);	
	}
	
	function returnSinceid(){
		$db = spClass('db_mybuy');
		$sql = "select weiboid as a from ".DBPRE."mybuy order by id desc limit 1";
		$rs = $db->findSql($sql);
		if(empty($rs[a])){$rs[a]=0;}
		return (string)$rs[a];
	}
	
	function show(){
		$n = urldecode($this->spArgs("n"));
		$db = spClass('db_mybuy');
		$rs = $db->findAll(array("weibonick"=>$n,"status"=>1),"time desc");
		foreach($rs as $r){
			$t = (string)date("Y-m-d",strtotime($r['time'])) ;
			$m[$t][]=$r;
		}
		$this->n = $n;
		$this->m=$m;
		$this->display("theme/default/mybuy.html");
	}
	

	
	
}