<?php
//产品库逻辑
class mybuy extends top{

	function __construct(){
		  parent::__construct(); 
		  require_once("init/Extensions/saetv2.ex.class.php");
		  set_time_limit(0);
	}	
	
	function index(){
		$this->show();
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
		if($rs->error){
			echo $rs->error;
			exit;
		}
		
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
		echo "newest id:".$since_id;
		//$since_id ='3532886654992203';
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
		echo "max page:$realPage";
	//echo "get maxpage:$realPage ok";
		$db = spClass('db_mybuy');
		while($realPage>=1){
			arsort($tmp[$realPage]);
			foreach ($tmp[$realPage] as $k => $v) {
				if(!$db->find(array('weiboid'=>$v['weiboid']))&&!empty($v['pic']))
				//print_r($v);
				$rs = $db->create($v);
			}	
			//确定是否有非重复数据				
			$realPage--;
		};
	}
	
	//通知用户
	function notice($weibonick,$id){
		$p['comment']="正玩已记录，这里可以看到您玩过的历史：http://www.zplaying.com/mybuy/show/n/".urlencode($weibonick);
		//$p['status']="正玩已记录，这里可以看到您玩过的历史：http://www.zplaying.com/mybuy/show/n/".urlencode($weibonick);
		//$p['is_comment']=3;
		$p['access_token']=$this->get_accesstoken();
		$p['id']=$id;
		//$url="https://api.weibo.com/2/statuses/repost.json";
		$url="https://api.weibo.com/2/comments/create.json";
		$result = SaeTOAuthV2::oAuthRequest($url,"POST",$p);
		$rs = json_decode($result);	
	}
	
	function returnSinceid(){
		$db = spClass('db_mybuy');
		$sql = "select weiboid as a from ".DBPRE."mybuy order by id desc limit 1";
		$rs = $db->findSql($sql);
		if(empty($rs[0][a])){$rs[0][a]=0;}
		return (string)$rs[0][a];
	}
	
	function show(){
		$n = urldecode($this->spArgs("n"));
		//获取全部商品
		$db = spClass('db_mybuy');
		
		if(!$this->spArgs("tagId")){
			if($n&&$n!='大家'){
				$cond = array("weibonick"=>$n,"status"=>1);
			}else{
				$cond = array("status"=>1);
			}
			
			$rs = $db->spLinker()->spPager($this->spArgs('page', 1), 10)->findAll($cond,"time desc");
			//print_r($rs);exit;
			$this->pager = $db->spPager()->getPager();
					
		}else{
			$rs = $db->returnTagGoods($this->spArgs("tagId"));
			
		}
			$this->n = $n?$n:'大家';
			foreach($rs as $r){
				$t = (string)date("Y-m-d",strtotime($r['time'])) ;
				$m[$t][]=$r;
			}
			$this->m=$m;	
		//print_r($this->m);exit;

		//print_r($this->pager);exit;
		
		//获取全部tag
		$this->tagList = $db->returnUserTags($n);
		$this->display("theme/default/mybuy.html");
	}
	
	function detail(){
		$nowUrl = ('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']); 
		$_SESSION["jumpUrl"]=$nowUrl;
		$id=$this->spArgs("id");
		
		$db = spClass('db_mybuy');
		$this->d =  $db->detail($id);
		if(!$this->d['product']['buy_url']){
			$this->post=$this->spArgs("post");
		}
		//print_r($this->d);
		$this->display("theme/default/mybuydetail.html");
	}
	
	function requestBuy(){
		//$this->needLogin();		
		$weiboId = $this->spArgs("weiboId");
		$detailId = $this->spArgs("detailId");
		if(!$_COOKIE[$weiboId]){
			$db=spClass("db_alertBuy");
			$db->newAlert($weiboId,$detailId);
			
			setcookie($weiboId,1,time()+3600);
		};
		
		$this->api_success("购买地址请求中。。等待回复");
	}
	
	function postBuy(){
		$this->needLogin();
		$db = spClass("db_mybuy");
		$weiboId=$this->spArgs("weiboId");
		
		$rs = $db->find(array('weiboid'=>$weiboId));
		if($rs['weibonick']!=$_SESSION['openconnect']['weibo']['name']){
			$this->api_success("对不起，您不是原作者。。等待作者分享购买地址吧||或者您还没有新浪微博登录？点击这里登录<a href=".spUrl("openconnect","weibo").">sina微博登录</a>");
			return;
		}
		
		$url=$this->spArgs("url");
		$id=$this->spArgs("detailId");
		if(isset($id)&&isset($url)){
			$db = spClass('db_mybuy');
			$db->inputUrl($id,$url,$weiboId);
			$db = spClass('db_notice');
			$db->buyAlertNotice($id);
			$this->api_success("修改完成");
			return;
		}else{
			//$this->postForm = $this->spArgs("postForm");
			$this->api_success("请输入购买地址：<input id=url name=url type=text style='width:400px'/>");
		}
	}
	
	function crontabPost(){
		$db=spClass('db_alertBuy');
		$rs =$db->findSql("select *,sum(times) as sumTimes from ".DBPRE."alertbuy where done=0 group by weiboId limit 10");
		//$rs = $db->findAll(array("done"=>0)," id desc","","10");
		foreach($rs as $r){
			switch($r['sumTimes']){
				case ($r['sumTimes']<=2):
					$this->postWeibo($r['sumTimes'],$r['weiboId'],$r['detailId']);
				break;
		
				case ($r['sumTimes']==5):
					$this->postWeibo($r['sumTimes'],$r['weiboId'],$r['detailId']);
				break;	
				
				case ($r['sumTimes']>=9):
					$this->postWeibo($r['sumTimes'],$r['weiboId'],$r['detailId']);
				break;					
			}

			sleep(1);
		}
		
	}
	
	function postWeibo($times=1,$id,$detailId){
		$p['comment']="有".$times."人在求这件好东东的购买地址,可以分享一下吗？把购买链接贴到这里吧。http://www.zplaying.com/mybuy/detail/post/1/id/".$detailId;
		$p['access_token']=$this->get_accesstoken();
		$p['id']=$id;
		//$url="https://api.weibo.com/2/statuses/repost.json";
		$url="https://api.weibo.com/2/comments/create.json";
		$result = SaeTOAuthV2::oAuthRequest($url,"POST",$p);
		$rs = json_decode($result);	
	}
}