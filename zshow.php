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
			$this->display('zlist.html');
		}else{
			err404('您查看的内容可能已经修改或者删除。');	
		}
		
		
	}
	
	
	//获取产品列表
	function getProduct($bid){
		$db = spClass('db_product');
		$sql = "SELECT * FROM ".DBPRE."product,".DBPRE."blog_product,".DBPRE."company WHERE ".DBPRE."company.id=".DBPRE."product.company_id and ".DBPRE."product.id = ".DBPRE."blog_product.product_id AND ".DBPRE."blog_product.blog_id=".$bid;
		$rs = $db->findSql($sql);
		foreach($rs as $k=>$r){
			$rs[$k]["tags"]=$this->getThisTag($r['product_id'],$this->uid);
		}
		//print_r($rs);
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
	
	
	
    }
?>