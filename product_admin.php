<?php
require_once('product.php');
class product_admin extends product{
	function __construct(){
		parent::__construct();
		$this->get = $this->spArgs();
        if($_SESSION['admin'] != 1){
            prient_jump(spUrl('main'));
        }
		$db = spClass('db_product');
		$this->db = $db;
	}
	
	function productList(){
		$db = spClass('db_product');
		$rs = $this->db->spLinker()->spPager($this->spArgs('page', 1), 20)->findAll();
		$this->product = $rs;
		$this->display('admin/product.html');
	}
	
	function edit(){
		//$this->db = spClass('db_product');
		$rs = $this->db->spLinker()->find(array("id"=>$this->spArgs('id')));
		$this->p = $rs;
		//print_r($this->spArgs('id'));
		$this->display('admin/product_edit.html');
		//print_r($rs);
	}
	
	function update(){
		$companyId = $this->processCompany($this->spArgs('company'));
		$_newRow = array(
						"company_id"=>$companyId,
						"year"=>$this->spArgs('year'),
						"style"=>$this->spArgs('style'),
						"info"=>$this->spArgs('info'),
						"buy_url"=>$this->spArgs('buy_url'),
						"pass"=>$this->spArgs('pass'),
					);
		$this->db->spLinker()->update(array("id"=>$this->spArgs('id')),$_newRow);
		header('Location:'.spUrl('product_admin','productList'));
	}
	
	function del(){
		$this->db->delete(array("id"=>$this->spArgs('id')));//删除产品库表，不删除公司表
		$_db =  spClass('db_blog_product');
		$_db->delete(array("product_id"=>$this->spArgs('id')));//删除blog和产品库对应关系
		header('Location:'.spUrl('product_admin','productList'));
	}
}
?>