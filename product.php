<?php
/////////////////////////////////////////////////////////////////
//云边开源轻博, Copyright (C)   2010 - 2011  qing.thinksaas.cn 
//EMAIL:nxfte@qq.com QQ:234027573                              
//$Id: user.php 866 2012-06-16 15:45:15Z anythink $ 


//产品库逻辑
class product extends top
{ 

	function __construct(){  
        parent::__construct();		
		$this->productList = array();//如果此变量为空，说明当前对应文章没有包含产品库数据，需要增加
		
		if($this->spArgs('bid')){
			$this->_bid=$this->spArgs('bid');
			
			//判断权限
			if($_SESSION['admin'] != 1){
				if($this->getUid($this->_bid) != $this->uid){
					$this->error('您没有权利编辑该文章',spUrl('main','index'));
				}
			}
			
			$this->productList = $this->getProductList($this->_bid);			
		};
     }  
	
	function index(){
		//赋值给模板
	 	$t['productList'] = $this->productList;	//赋值已关联产品数据 
		//赋值已有产品搜索库字段
		$t['bid'] = $this->spArgs('bid');	
		//echo $this->spArgs('bid');
		$this->p = $t;
		//print_r($this->p);
		 $this->display("product.html");
		 
		}
		
	function newShow(){
		$t['productList'] = $this->productList;	//赋值已关联产品数据 
		//赋值已有产品搜索库字段
		$t['bid'] = $this->spArgs('bid');	
		//echo $this->spArgs('bid');
		$this->p = $t;
		$this->display("newproduct.html");
	}


	function newProduct(){
		//todo blogid没有写入
		//首先保存公司信息，如果没有，新增，返回公司id，如果有，返回公司id
		$companyId = $this->processCompany($this->spArgs('company'));
		//保存产品信息
		$_add = spClass('db_product');
		$_newRow = array(
						"company_id"=>$companyId,
						"year"=>$this->spArgs('year'),
						"style"=>$this->spArgs('style'),
						"info"=>$this->spArgs('info'),	
					);
	

		$rs = $_add->create($_newRow);

		//保存blog和product的关联关系
		/*$_add = spClass('db_blog_product');
		$_newRow = array(
				"blog_id"=>$this->spArgs('blog_id'),
				"product_id"=>$rs
			);
		$_add->create($_newRow);*/
		//print_r($this->spArgs());exit;		
		//header('Location:'.spUrl('main'));
		//$liObj = $this->spArgs('company') . $_newRow['year']."年".$_newRow['style'];
		if($rs){echo $rs;}
		}
	
	function newProduct2(){
		//todo blogid没有写入
		//首先保存公司信息，如果没有，新增，返回公司id，如果有，返回公司id
		$companyId = $this->processCompany($this->spArgs('company'));
		//保存产品信息
		$_add = spClass('db_product');
		$_newRow = array(
						"company_id"=>$companyId,
						"year"=>$this->spArgs('year'),
						"style"=>$this->spArgs('style'),
						"info"=>$this->spArgs('info'),	
					);
	

		$rs = $_add->create($_newRow);

			if($rs){
				prient_jump(spUrl('zshow','product',array('pid'=>$rs)));
			}
		}
	
	function isEdit(){
			
			return $_productList;
		}
		
	function getProductList($id){
			$prodcut_blog = spClass('db_blog');	
			$sql = "SELECT product_id,uid,YEAR,style,company,info FROM ".DBPRE."blog,".DBPRE."blog_product,".DBPRE."product,".DBPRE."company WHERE ".DBPRE."product.company_id = ".DBPRE."company.id AND ".DBPRE."blog_product.blog_id = ".DBPRE."blog.bid AND ".DBPRE."product.id = ".DBPRE."blog_product.product_id AND  bid = ".$id;	
			$temp = $prodcut_blog->findSql($sql);
			return $temp;
		}
	
	function search(){
		//生成搜索提示字段
		$db = spClass('db_product');
		$rs = $db->spLinker()->findAll();
		
		//格式化
	
			foreach($rs as $r){
				$text[] = "{\"id\":\"".$r['id']."\",\"text\":\"".$r['company']['company']. $r['year']."年  ".$r['style']."\"}";
				
				//'[{"id":"AD","text":"osprey 2012年 atmos 35"},{"id":"AE","text":"burton 2011年 evo"},{"id":"AF","text":"giant 2011年 reign"}	]'
			}
			
		$json = "var testData = {};testData.countryCodes =[".join(",",$text)."]";
		
		echo $json;
		}
		
	function createBuyUrl(){
		
		}
		
	function processCompany($name){
		$company = spClass('db_company');
		$data = array('company'=>$name);
		//查找
		$rs = $company->find($data);
		if(empty($rs)){
			//如果没有，写入，并且返回id
			$id = $company->create($data);
		}else{
			$id = $rs['id'];
			
			}
		return $id;
	}
	
	function save(){
		$db = spClass('db_blog_product');
		$blog_id=$this->spArgs('blog_id');
		//删除原始关联
		$db->delete(array("blog_id"=>$blog_id));
		//保存新关联数据
		$newData = array();

		foreach (array_unique($this->spArgs('product')) as $key => $value) {
			$newData[]=array("blog_id"=>$blog_id,"product_id"=>$value);
		}
		
		$db->createAll($newData);
		header('Location:'.spUrl('main'));
	}
	
	function getUid($bid){
		$db = spClass('db_blog');	
		$rs = $db->find(array("bid"=>$bid));
		return $rs["uid"];
	}
	
	

}

/*
 * 
 CREATE TABLE `th_blog_product` (
 `blog_id` int(10) unsigned DEFAULT NULL,
 `product_id` int(10) unsigned DEFAULT NULL
 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 

CREATE TABLE `th_company` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `company` char(100) CHARACTER SET latin1 DEFAULT NULL,
 PRIMARY KEY (`id`)
 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 

CREATE TABLE `th_product` (
 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 `company_id` int(10) unsigned DEFAULT NULL,
 `year` year(4) DEFAULT NULL,
 `style` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
 `info` text CHARACTER SET latin1,
 `buy_url` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
 `pass` tinyint(1) DEFAULT '0',
 PRIMARY KEY (`id`)
 )ENGINE=MyISAM DEFAULT CHARSET=utf8 
 * /
 */