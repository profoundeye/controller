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
		$this->_host = $this->spArgs('host');
		$values = $this-> process($info);
		print_r($values);
		//curl所有图片信息存在缓存目录
		
		//显示修改数组
		
		//调用正常photo保存模式
		
		$this->display("url_post.html");
	} 
	
	function process($info){
		$a = preg_split('/<p>(.*?)<\/p>|<h1.*?>(.*?)<\/h1>|<img.*?src="(.*?)".*?>/s',$info,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i){
			$temp = trim(preg_replace('/<.*?>/',"",$i));
			if(!empty($temp)){$output['org'][]=$temp;}
		}
		
		$temp = -1;
		foreach($output['org'] as $k=>$o){
			$isImg = $this->isImg($o);
			//如果k=0，标题
			if($k==0){$output['org']['title']=$o;}
			if(!$isImg&&$temp==-1&&$k!=0){$output['org']['headline'].=$o;}
			if($isImg){
				if(preg_match("/http/is", $o)){
					$output['imgText'][$k]['img']=$o;
				}else{
					$output['imgText'][$k]['img']="http://".$this->_host.$o;
				}
				
				$temp = $k;
			}
			if(!$isImg&&$temp!=-1){
				$output['imgText'][$temp]['text'].=$o;
			}
		}
		return $output;
	}
	
	function isImg($str){
		if(preg_match("/png|jpeg|jpg|gif/is", $str)){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	function kuayu(){
		//echo '<form method="get" action="http://localhost/testyunbian/index.php?c=get&a=input"><textarea id="info name="info"> </textarea><input type="submit" name="button" id="button" value="提交" /></form>';
	}
}
?>