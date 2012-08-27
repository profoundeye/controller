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
			$this->productList = $this->getProductList($this->_bid);
			
		};
     }  
	
	function index(){
		//赋值给模板
	 	$t['productList'] = $this->productList;	//赋值已关联产品数据 
		//赋值已有产品搜索库字段
		$t['bid'] = $this->spArgs('bid');	
		//echo $this->spArgs('bid');
		$this->t = $t;
		 $this->display("product.html");
		 
		}
		
	function add(){
		//todo blogid没有写入
		//首先保存公司信息，如果没有，新增，返回公司id，如果有，返回公司id
		$companyId = $this->processCompany($this->spArgs('company'));
		//保存产品信息
		$_add = spClass('db_product');
		$_newRow = array(
						"company_id"=>$companyId,
						"year"=>$this->spArgs('year'),
						"style"=>$this->spArgs('style'),
						"info"=>utf8_substr($this->spArgs('info')),
						"blog_product"=>array(
											"blog_id"=>$this->spArgs('blog_id')														
										)		
					);
	

		//$_newRow('company_id')=$companyId;
		$rs = $_add->create($_newRow);
		
		//保存blog和product的关联关系
		$_add = spClass('db_blog_product');
		$_newRow = array(
				"blog_id"=>$this->spArgs('blog_id'),
				"product_id"=>$rs
			);
		$_add->create($_newRow);
		
		header('Location:'.spUrl('main'));
		}
		
	function isEdit(){
			
			
			return $_productList;
		}
		
	function getProductList($id){
			$prodcut_blog = spClass('db_blog');	
			$sql = "SELECT product_id,YEAR,company,info FROM th_blog,th_blog_product,th_product,th_company WHERE th_product.company_id = th_company.id AND th_blog_product.blog_id = th_blog.bid AND th_product.id = th_blog_product.product_id AND  bid = ".$id;	
			//echo $sql;exit;
			$temp = $prodcut_blog->findSql($sql);
			return $temp->blog_product;
		}
	
	function search(){
		//生成搜索提示字段
		$db = spClass('db_product');
		$rs = $db->spLinker()->findAll();
		
		//格式化
	
			foreach($rs as $r){
				
				$text[] = "{\"id:\":\"".$id."\",\"text\":\"".$r['company']['company']."&nbsp&nbsp". $r['year']."年  ".$r['style']."\"}";
				
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
}