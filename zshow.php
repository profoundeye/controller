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
			$this->meId = $_SESSION['user']['uid'];
			$this->tagArticle = $this->getSameTagArticle($rs['tag'],$this->spArgs('bid'));
			$this->tag = split(",",$rs['tag']);
			$this->display('zlist.html');
		}else{
			err404('您查看的内容可能已经修改或者删除。');	
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
		$this->thisProductInfo = $this->_getProductInfo($pid);
		$this->company = $this->thisProductInfo[0]['company']['company'];
		$this->blogInfo = $this->_getProductBlog($pid);
		$this->sameCompanyProduct = $this->_getSameCompayProduct($this->thisProductInfo[0]['company_id']);
		//print_r($this->sameCompanyProduct);
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
		}
	
		return $rs;
	}
	
	function _getProductBlog($pid){
		$db = spClass('db_blog_product');
		$rs = $db->findAll(array("product_id"=>$pid));
		foreach ($rs as $key => $v) {
			$rs[$key]["blog"]=$this->_returnBlogContent($v['blog_id']);
		}
		return $rs;
	}
	
	function _returnBlogContent($bid){
		$sql = "SELECT * FROM `".DBPRE."blog` AS b  where b.open = 1 and b.bid = '$bid'";
		$rs = spClass('db_blog')->spLinker('user')->findAll(array('bid'=>$bid));
		//print_r($rs);exit;
			$rs['body'] = split_attribute(converPic($rs[0]['body']));
			$rs['tag'] = split(",",$rs['tag']);	
		return $rs;	
    }
	


    }
?>