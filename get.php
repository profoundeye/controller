<?php
    /*通过js获取url内容
	 * post提交源码
	 * 分析内容之后，写入框
	 * 完成
	 */
class get extends top{ 
	function __construct(){  
        parent::__construct();
		$this->productList = array();//如果此变量为空，说明当前对应文章没有包含产品库数据，需要增加
		
		if($this->spArgs('bid')){
			$this->_bid=$this->spArgs('bid');
			$this->productList = $this->getProductList($this->_bid);
			
		};		 
     } 
	
	function input(){
		//还要增加来源出处
		$info = $this->spArgs('info');
		$this-> process($info);
		$this->display("url_post.html");
	} 
	
	function process($info){
		$a = preg_split('/<p>(.*?)<\/p>|<h1.*?>(.*?)<\/h1>|<img.*?src="(.*?)".*?>/s',$info,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i){
			$temp = trim(preg_replace('/<.*?>/',"",$i));
			if(!empty($temp)){$output[]=$temp;}
		}
		print_r($output);
	}
	
	function kuayu(){
		//echo '<form method="get" action="http://localhost/testyunbian/index.php?c=get&a=input"><textarea id="info name="info"> </textarea><input type="submit" name="button" id="button" value="提交" /></form>';
	}
}
?>