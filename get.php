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
		//$this->result = array();
		$info = $this->spArgs('info');
		$this->_host = $this->spArgs('host');
		$this->values = $this->process($info);
		$this->tmpfile = spClass('uploadFile')->selectuptype(6);
		$this->save_image(array_keys($this->values['imgText']));
		
		//print_r($this->result);
		//print_r($this->values);

		//curl所有图片信息存在缓存目录
		
		//写入缓存文件路径到th_attachments表
		
		//调用正常photo保存模式
		
		$this->display("models/photoGet.html");
	} 
	
	function buildTemplate(){
		
	}
	
	function saveToAttachments($file,$thisPath){
		$db = spClass('db_attach');		
		foreach ($file as  $f) {
			$localFile  = $thisPath[$f];
			$mimie = pathinfo($localFile);
			//echo $mimie;
			if($mimie["extension"]){
			$data = array("bid"=>0,"path"=>$localFile,"blogdesc"=>"","filesize"=>filesize($localFile),"mime"=>$mimie["extension"],"uid"=>$_SESSION['user']['uid'],"time"=>time());
			$id = $db->create($data);	
		
			static $result;
			$result[]=array("id"=>$id,"img"=>$localFile,"desc"=>$this->values["imgText"][$f]?$this->values["imgText"][$f]:"");
			}
		}		
		
		
		$this->result=$result;
	}
	
	
	function process($info){
		$a = preg_split('/<p>(.*?)<\/p>|<h1.*?>(.*?)<\/h1>|(<img("[^"]*"|\'[^\']*\'|[^\'">])*>)/s',$info,-1,PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i){
			if(!empty($i)){
			if(preg_match("/^<img/i",$i)){
				unset($match);
				$rs = preg_match("/src=\"(.*?)\"/", $i,$match);
				$temp = $match[1];
			}else{
				if($i=='"1"'||$i=='"readable_class_big_image"'){$i="";}//匹配sina blog的问题
				$temp = trim(preg_replace('/<.*?>/',"",$i));				
			}
			
			if(!empty($temp)){$output['org'][]=$temp;}
			}
		}
		//print_r($output);exit;
		$temp = -1;
		//print_r($info);
		foreach($output['org'] as $k=>$o){
			$isImg = $this->isImg($o);
			//如果k=0，标题
			if($k==0){$output['title']=$o;}
			if(!$isImg&&$temp==-1&&$k!=0){$output['headline'].=$o;}
			if($isImg){
				if(preg_match("/http/is", $o)){
					$key=$o;
				}else{
					$key="http://".$this->_host."/".$o;			
				}
							
				if(is_string($isImg)){
					$key=$key.".".$isImg;
				}
				$temp = $key;
				$output['imgText'][$key]="";
			}
			if(!$isImg&&$temp!=-1){
				$output['imgText'][$temp].=$o;
			}
		}
		//print_r($output);exit;
		return $output;
	}
	
	function isImg($str){
		if(preg_match("/png|jpeg|jpg|gif/is", $str)){
			return true;
		}else{
			if(preg_match("/http:/is",$str)){
				$http = get_headers($str,1);
				if(preg_match("/png|jpeg|jpg|gif/is",$http["Content-Type"])){
					return str_replace("image/", "", $http["Content-Type"]);
				}
			}
			return false;
		}
	}
	
	
	
	function tools(){
		$this->display("url_post.html");
	}
	

	
	function save_image($urls) {
	    $queue = curl_multi_init();
		$map = array();
	    foreach ($urls as $url) {
	        $ch = curl_init();
	 
	        curl_setopt($ch, CURLOPT_URL, $url);
	        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_HEADER, 0);
	        curl_setopt($ch, CURLOPT_NOSIGNAL, true);
	 
	        curl_multi_add_handle($queue, $ch);
	        $map[(string) $ch] = $url;
	    }
	 
	    $responses = array();

	    do {
	        while (($code = curl_multi_exec($queue, $active)) == CURLM_CALL_MULTI_PERFORM) ;
	 
	        if ($code != CURLM_OK) { break; }
	 
	        // a request was just completed -- find out which one
	        while ($done = curl_multi_info_read($queue)) {
	        	//保存图片，更新数据库
	  
				$data = curl_multi_getcontent($done['handle']);
				$file = $map[(string) $done['handle']];
				$thisPath[$file] = $this->tmpfile.'/'.basename($file);
				$fp = fopen($thisPath[$file],'x');
				fwrite($fp, $data);
	   			fclose($fp);			
	 			 	
	            curl_multi_remove_handle($queue, $done['handle']);
	            curl_close($done['handle']);
	        }
	 
	        // Block for data in / output; error handling is done by curl_multi_exec
	        if ($active > 0) {
	            curl_multi_select($queue, 0.5);
	        }
	 
	    } while ($active);
	 
	    curl_multi_close($queue);
		$this->saveToAttachments($urls,$thisPath);	
	    return true;
	}

	
}
?>