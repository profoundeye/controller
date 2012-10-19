<?php
    class tagit extends top{
    	function __construct(){  
       		parent::__construct(); 
			$this->needLogin();
    	}
		
		function thisplaying(){
			//返回对话框内容
			if($this->spArgs('interest')=="在玩"){
				$playing = "checked";
			}else{
				$play = "checked";
			}

			$thisTags = $this->thisTag($this->spArgs("productId"),$this->uid);
			foreach ($thisTags as $k => $v) {
				if($v['producttags'][0]['tag']!='在玩'&&$v['producttags'][0]['tag']!='想玩')
				$thisTag.=$v['producttags'][0]['tag']." ";
			}
			$str = '<ul id="tagIt">
			<li class="want"><input type="radio" name="interest" value="在玩" '.$playing.' />在玩
			<input type="radio" name="interest"  value="想玩" '.$play.' />想玩
			</li><li class="tagInput">标签(多个标签用空格分隔): <input id="tags" name="tags" type="text" value="'.$thisTag.'"></li>';
			//插入我的标签
			$str .='<li id="mytags" class="clearfix"><dl><dt>我的标签:</dt><dd>';
			$mytag = $this->myTag();
			foreach ($mytag as $k => $v) {
				if($v['producttags'][0]['tag']!='在玩'&&$v['producttags'][0]['tag']!='想玩')
				$str.="<span>".$v['producttags'][0]['tag']."</span>";
			}
			$str .='</dd></dl></li>';
			//插入产品的标签
			
			$str .='<li id="productTags" class="clearfix"><dl><dt>常用标签:</dt><dd>';
			$productTags = $this->productTag($this->spArgs("productId"));
			foreach ($productTags as $k => $v) {
				if($v['producttags'][0]['tag']!='在玩'&&$v['producttags'][0]['tag']!='想玩')
				$str.="<span>".$v['producttags'][0]['tag']."</span>";
			}
			$str .='</dd></dl></li>';
			$str .='</ul>';
			$this->api_success($str);
			//return $str;
		}
		
		function myTag(){
			//返回用户常用的10个tag
			$db = spClass("db_product_tag_user");
			$rs = $db->spLinker()->findAll(array("user_id"=>$this->uid),"tag_id desc","",10);
			$temp = array();
			foreach($rs as $r){				
				if(!in_array($r['producttags'][0][tag],$temp)){
					$temp[] = $r['producttags'][0][tag];
					$outPut[]= $r;
				}
			}
			return $outPut;
		}
		
		function productTag($productId){
			//返回产品常用tag
			$db = spClass("db_product_tag_user");
			$rs = $db->spLinker()->findAll(array("product_id"=>$productId),"tag_id desc","",10);
			return $rs;			
		}
		
		function thisTag($productId,$uid){
			//返回已有tag
			$db = spClass("db_product_tag_user");
			$rs = $db->spLinker()->findAll(array("product_id"=>$productId,"user_id"=>$uid),"tag_id desc","",10);
			return $rs;			
		}
		
		function saveTag(){
			//删除已有的标签对应关系 
			$db = spClass("db_product_tag_user");
			$data = array("user_id"=>$this->uid,"product_id"=>$this->spArgs("productId"));
			$db->delete($data);
			//保存tag
			$tags = $this->spArgs("tags");
			if(empty($tags)){$tags="想玩";}
			$tags = split(" ", $this->spArgs("tags"));
			foreach($tags as $t){
				if(!empty($t)){
					$db = spClass("db_producttags");
					//是否tag已经存在
					$rs = $db->find(array("tag"=>$t));
					if(!$rs["id"]){
						$data = array("tag"=>$t);
						$rs["id"] = $db->create($data);
					}
					//保存和tag之间的关系
					$db = spClass("db_product_tag_user");
					$data = array("user_id"=>$this->uid,"tag_id"=>$rs["id"],"product_id"=>$this->spArgs("productId"));

					$rs = $db->find($data);
					if(!$rs){
						$rs = $db->create($data);
					}				
					if($rs===false){
						$this->api_error("标签保存失败");
					}
				}
			}
			
			
			
			
			
		}
		
		function test(){echo "ok";}
		
    }
?>