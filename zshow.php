<?php
	require_once("userblog.php");
    class zshow extends userblog{
    public function show()
	{
		$this->getUserSkin($this->spArgs('bid'));
		$this->getMyFollow();
		$this->getMyLook();
		$this->pmNum();
		$this->fava = $this->getBlogFava();
		$this->isfollow = $this->isFollow();
		$this->bid = intval($this->spArgs('bid'));
		//检测是否存在
		$sql = "SELECT * FROM `".DBPRE."blog` AS b  where b.open = 1 and b.bid = '$this->bid'";
		$rs = spClass('db_blog')->find(array('bid'=>$this->bid));
		if($rs)
		{
			spClass('db_blog')->incrField(array('bid'=>$this->spArgs('bid')), 'hitcount'); 
			
			$this->p = $this->getProduct($this->bid);
			
			$this->body = split_attribute(converPic($rs['body']));
			$this->rs = $rs;
			$this->meId = $_SESSION['uid'];
			$this->tagArticle = $this->getSameTagArticle($rs['tag'],$this->spArgs('bid'));
			$this->shareImg = $this->returnFirstImg();
			$this->tag = split(",",$rs['tag']);
			$this->display('zlist.html');
			
		}else{
			err404('您查看的内容可能已经修改或者删除。');	
		}
		
		
	}
	
	function returnFirstImg(){
		if(isset($this->body['attr']['img'][0]['url'])){
			return converPic($this->body['attr']['img'][0]['url']);
		}
	}
	
	//获取产品列表
	function getProduct($bid){
		global $spConfig;
		$db = spClass('db_product');
		$sql = "SELECT * FROM ".DBPRE."product,".DBPRE."blog_product,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id and ".DBPRE."product.id = ".DBPRE."blog_product.product_id AND ".DBPRE."blog_product.blog_id=".$bid;
		$rs = $db->findSql($sql);
		foreach($rs as $k=>$r){
			$rs[$k]["tags"]=$this->getThisTag($r['product_id'],$this->uid);
			$rs[$k]["fans"]['playing']=$this->getProductFans($r['product_id'],$spConfig['playing']);
			$rs[$k]["fans"]['want']=$this->getProductFans($r['product_id'],$spConfig['want']);
		}
		//print_r($rs);exit;
		return $rs;
	}
	
	function getProductFans($pid,$p){
		//$rs = spClass('db_product_tag_user')->spLinker('productUser')->findAll(array("product_id"=>$pid,"tag_id"=>$p),"","user_id");
		$sql = "SELECT * FROM ".DBPRE."product_tag_user,".DBPRE."member WHERE ".DBPRE."product_tag_user.user_id=".DBPRE."member.uid AND ".DBPRE."product_tag_user.product_id=".$pid." AND ".DBPRE."product_tag_user.tag_id=".$p." order by source desc";
		$rs = spClass('db_product_tag_user')->findsql($sql);
		//print_r($rs);exit;
		return $rs;
	}
	
	function getProductPic($id=3){
		$db = spClass('db_blog_product');
		$pic = array();
		$rs = $db->spLinker()->findAll($id);
		foreach($rs as $r){
			if($r['attachments']){
				$pic= array_merge($pic,$r['attachments']);
			}			
		}
		foreach($pic as $k=>$p){
			$pic[$k]['picUrl'] = converPic($p['path'],",h_100");
		}
		return $pic;
	}

	function getThisTag($productId,$uid){
		$db = spClass("db_product_tag_user");
		$rs = $db->spLinker()->findAll(array("product_id"=>$productId,"user_id"=>$uid),"tag_id desc","",10);
		foreach ($rs as $k => $v) {
			if($v['producttags'][0]['tag']!='在玩'&&$v['producttags'][0]['tag']!='想玩'){
				$str['tags'].="<span>".$v['producttags'][0]['tag']."</span>";
			}else{
				$str['playing'] = $v['producttags'][0]['tag'];
			}			
		}
		return $str;	
	}
	
	function getSameTagArticle($tag,$bid){
		if(empty($tag)||empty($bid)){return ;}
		$tags = split(",", $tag);
		foreach ($tags as $t) {
			$data = spClass('db_tags')->getArticleFromTag($t);
			foreach($data as $k=>$d){
				if($d['bid']!=$bid)$id[]=$d['bid'];
			}
		}
		$id = array_unique($id);
		if(empty($tag)||empty($id)){return ;}
		unset($data);
		
		$data  =spClass('db_blog')->findAll("bid in (".join(",",$id).") and open=1","bid desc","title,bid,body","10");

			foreach($data as $ik=>$i){
				$outPut[$k][$ik]['bid']=$i["bid"];
				$outPut[$k][$ik]['title']=$i["title"];
				$outPut[$k][$ik]['body']=split_attribute(converPic($i["body"]));
			}
		
		return $outPut;
	}
	
	
	function product(){
		$pid = $this->spArgs("pid");
		$this->productId = $pid;
		$this->alert = $this->spArgs("alert");
		$this->p = $this->_getProductInfo($pid);
		$this->company = $this->p[0]['company'];
		$this->blogInfo = $this->_getProductBlog($pid);
		$this->sameCompanyProduct = $this->_getSameCompayProduct($this->p[0]['company_id']);
		$this->meId = $_SESSION['uid'];
		//print_r($this->sameCompanyProduct);exit;
		$this->display('zproduct.html');
	}
	
	
	function _getSameCompayProduct($cid){
		//echo $cid;exit;
		$rs = spClass('db_product')->spLinker("company")->findAll(array("company_id"=>$cid));
		
		foreach($rs as $k=>$r){
			$rs[$k]["productInfo"]=$this->_getProductInfo($r['id']);
		}
		return $rs;
	}
	
	
	function _getProductInfo($pid){
		global $spConfig;
		$db = spClass('db_product');
		$rs = $db->spLinker("company")->findAll(array("id"=>$pid));
		
		foreach($rs as $k=>$r){
			$rs[$k]["tags"]=$this->getThisTag($r['id'],$this->uid);
			$rs[$k]["fans"]['playing']=$this->getProductFans($r['id'],$spConfig['playing']);
			$rs[$k]["fans"]['want']=$this->getProductFans($r['id'],$spConfig['want']);
			$rs[$k]["product_id"]=$rs[$k]['id'];
			$rs[$k]["company_id"]=$rs[$k]['company']['id'];
			$rs[$k]["company"]=$rs[$k]['company']["company"];
			
		}
	
		return $rs;
	}
	
	function _getProductBlog($pid){
		$db = spClass('db_blog_product');
		$rs = $db->findAll(array("product_id"=>$pid),"blog_id desc");
		foreach ($rs as $key => $v) {
			$temp =$this->_returnBlogContent($v['blog_id']);
			if($temp){
				if($temp[0]['type']==1)$rs["sms"][$key]["blog"]=$temp;
				if($temp[0]['type']==3)$rs["blog"][$key]["blog"]=$temp;
			}

		}
		return $rs;
	}
	
	function _returnBlogContent($bid){
		//$sql = "SELECT * FROM `".DBPRE."blog` AS b  where b.open = 1 and b.bid = '$bid'";
		$rs = spClass('db_blog')->spLinker('user')->findAll(array('bid'=>$bid));
		if($rs[0]["uid"]==$_SESSION['uid'] || $rs[0]["open"]==1||$rs[0]["type"]==1){
			$rs['body'] = split_attribute(converPic($rs[0]['body'],",h_125"));
			$rs['tag'] = split(",",$rs['tag']);	
			return $rs;
		}
    }
	  
	function me(){
		//返回头像信息。
		$uname = $this->spArgs("uname");
		$db = spClass("db_member");
		$this->userinfo = $db->find(array('username'=>$uname),"","uid,username,source,sign");
		$uid = $this->userinfo['uid'];
		
		
		//获取当前用户全部标签tag
		$sql ="SELECT distinct(tag),tag_id FROM ".DBPRE."product,".DBPRE."product_tag_user,".DBPRE."producttags WHERE ".DBPRE."product_tag_user.user_id=".$uid." and ".DBPRE."product_tag_user.tag_id= ".DBPRE."producttags.id";	
		$this->tags =$db->findSql($sql);
		
		//根据tag返回想玩在玩商品信息
		if($uname){
			foreach($this->tags as $t){
				$sql ="SELECT distinct(".DBPRE."product.id),img,style,year,info,buy_url,buy_dec,company FROM ".DBPRE."product,".DBPRE."product_tag_user,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id  and img<>''  and  ".DBPRE."product_tag_user.user_id=".$uid." and ".DBPRE."product_tag_user.tag_id=".$t['tag_id']." and ".DBPRE."product_tag_user.product_id=".DBPRE."product.id  order by id desc limit 9";				
				$pp[$t['tag']]=$db->findSql($sql);
			}		
		};
			foreach ($pp as $k => $p){
				if($k=="想玩"){					
					$l["想玩"]= $p;
				}else if($k=="在玩"){					
					$l["在玩"] = $p;
				}else{
					$temp[$k]=$p;
				}	
							
			}				
		
		if($l&&$temp){
			$this->p = $l+$temp;
		}else{
			if(!$temp){
				$this->p = $l;
			}else{
				$this->p = $temp;
			}
		}
		
//print_r($this->p);exit;
		$this->display('ztag.html');
	}
	
	function explorer(){
		/*传入两个参数，username\tagName 两个可以任意传递，或者2选一，
		 * username时，显示当前用户下全部标签，header上显示用户基本信息
		 * tagName时，显示tag该tag下全部的商品。tag分两种，文章tag ：atag，想玩，在玩标签tag。
		 * 如果什么参数都没有，默认跑最新的文章中涉及到的产品
		 * limit返回多少条数据，page翻页
		 */
		$uname = $this->spArgs("uname");
		$tag = $this->spArgs("tag");
	
		if($tag){
			$tagId = $this->_returnSomeInfo("",$tag);
			$sql ="SELECT distinct(".DBPRE."product.id),img,style,year,info,buy_url,buy_dec,company FROM ".DBPRE."product,".DBPRE."product_tag_user,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id and img<>'' and ".DBPRE."product_tag_user.tag_id=".$tagId['id']."  and ".DBPRE."product_tag_user.product_id=".DBPRE."product.id   order by id desc";
			$pageSpr = array("tag"=>$tag);
		};
		
		if($tag&&$uname){
			$db = spClass("db_member");
			$this->userinfo = $db->find(array('username'=>$uname),"","uid,username,source,sign");			
			
			$uid = $this->_returnSomeInfo($uname,"");
			$sql ="SELECT distinct(".DBPRE."product.id),img,style,year,info,buy_url,buy_dec,company FROM ".DBPRE."product,".DBPRE."product_tag_user,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id  and img<>''  and  ".DBPRE."product_tag_user.user_id=".$uid['uid']." and ".DBPRE."product_tag_user.tag_id=".$tagId['id']." and ".DBPRE."product_tag_user.product_id=".DBPRE."product.id  order by id desc";	
			
			$pageSpr = array("uname"=>$uname,"tag"=>$tag);

		}
		
		if(!$tag&&!$uname){
			$sql ="SELECT ".DBPRE."product.id,img,style,year,info,buy_url,buy_dec,company FROM ".DBPRE."product,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id and img<>'' order by id desc";
			$pageSpr = "";			
		}
		
		
		//最新的产品
		$db = spClass("db_blog_product");
		$p[$tag] = $db->spPager($this->spArgs('page', 1), 15)->findSql($sql);
		$this->pager = $db->spPager()->pagerHtml("zshow","explorer",$pageSpr);		
		$this->p = $p;
		$this->display('ztag.html');
	}

	function _returnSomeInfo($uname,$tag){
		if($uname){
			$db = spClass("db_member");
			$rs= $db->find(array('username'=>$uname),"","uid");
		}
		if($tag){
			$db = spClass("db_producttags");
			$rs = $db->find(array('tag'=>$tag),"","id");
		}	
		
		if($rs){return $rs;}else{exit;}
	}
	
	function imgKey(){
		$this->needLogin();
		$path = $this->spArgs("path");
		$pid = $this->spArgs("pid");	
		//path=http://localhost/testyunbian/attachs/12/10/16/26/eec36b7db1efba67d9a7ff3ab9bf60e7.jpg&pid=10
		$data = array("pid"=>$pid,"img"=>$path);

		$db = spClass("db_product");
		
		$rs = $db->updateField(array("id"=>$pid),"img",$path);
		var_dump($rs);
	}
	
    }
?>