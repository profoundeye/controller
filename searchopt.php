<?php

class searchopt extends top{

	public function __construct()
	{
		import(siteMap.php);		
		parent::__construct();
		$uri = trim(dirname($GLOBALS['G_SP']['url']['url_path_base']),'\/\\');
		if( '' == $uri ){ $uri = 'http://'.$_SERVER['HTTP_HOST']; 	}else{ $uri = 'http://'.$_SERVER['HTTP_HOST'].'/'.$uri; }
		$this->siteUrl =  $uri;
		$this->siteHost = 'http://'.$_SERVER['HTTP_HOST'];
	}

	public function index(){
		echo "<a href=".spUrl('searchopt','getrss',array('method'=>'index')).">index</a><br />";
		echo "<a href=".spUrl('searchopt','getrss',array('method'=>'recommend')).">recommend</a><br />";
		echo "<a href=".spUrl('searchopt','getrss',array('method'=>'discovery')).">discovery</a><br />";
		echo "<a href=".spUrl('searchopt','getrss',array('method'=>'userHome','uid'=>2)).">userHome</a><br />";
		echo "<a href=".spUrl('searchopt','getrss',array('method'=>'tag')).">tag</a><br />";
	}

	public function getrss(){
		$p = $this->spArgs();
		$method = $p['method'];
		$content = '';
		if($cache = spAccess('r','rss_'.$method)){
			$content = $cache;
		}else{
			switch ($method) {
				case 'index':
					$content = $this->indexRss();
					break;
				case 'recommend':
					$content = $this->recommendRss();
					break;
				case 'discovery':
					$content = $this->discoveryRss();
					break;
				case 'tag':
					$content = $this->tagRss();
					break;
				case 'userHome':
					$content = $this->userHomeRss();
				
				default:
					//$this->jump(spUrl('main','index'));
					$content = $this->indexRss();
			}
			//写缓存
			spAccess('w','rss_'.$method,$content,$this->cacheTime);
		}
		header('Content-type: application/xhtml+xml');
		echo $content;
		//dump($url);
	}

	public function getSiteMap(){
		$config = array('flags'=>'sitemap');
		$sitemap = spClass('sitemap',array($config));
		$data = array();
		//添加首页
		$data[] = array('loc' => $this->siteUrl,
	 					'priority' => 0.5,
	 					'lastmod' => time(),
	 					'changefreq' => 'aways'
	 				);
		//添加发现
		$data[] = array('loc' => $this->siteHost.spUrl('main','discovery'),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);

		//添加推荐
		$data[] = array('loc' => $this->siteHost.spUrl('main','recommend'),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);

		//自定义页面
		$page = spClass('db_cpage_cate')->findAll(NULL,NULL,'tags');
		foreach($page as $key=>$val){
			$data[] = array('loc' => $this->siteHost.spUrl('site','index',array('page'=>$val['tags'])),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);

		}

		//taglist
		$taglist = spClass('db_tags')->findAll(NULL,NULL,'title');
		foreach ($taglist as $key => $val) {
			$data[] = array('loc' => $this->siteHost.spUrl('blog','tag',array('tag'=>$val['title'])),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);
		}
		//每个人的页面
		$member = spClass('db_member')->findAll(NULL,NULL,'uid');
		foreach ($member as $key => $val) {
			$data[] = array('loc' => $this->siteHost.spUrl('userblog','index',array('uid'=>$val['uid'])),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);
		}

		//每篇文章
		$arti =  spClass('db_blog')->findAll(NULL,NULL,'bid');
		foreach ($arti as $key => $val) {
			$data[] = array('loc' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
					'priority' => 0.5,
					'lastmod' => time(),
					'changefreq' => 'aways'
				);
		}

		$sitemap->addAll($data);
		header('Content-type: application/xhtml+xml');
		echo $sitemap->create();
	}

	/**
	 * bulid index rss
	 */
	private function indexRss(){
		$config = array('flags'		=>'rss',
					'title' 	=>$this->yb['site_title'].'--首页',
					'link'  	=>$this->siteUrl,
					'desc'		=>$this->yb['site_desc'].'---首页',
					'lang'  	=>'zh-CN',
					'pubDate'	=>time(),
					'image'		=>array(
								'link'	=> $this->siteUrl,
								'url' 	=> $this->siteUrl.'/'.$GLOBALS['G_SP']['view']['config']['template_dir'].'/image/favicon.ico',
								'title' => $this->yb['site_title'].'--首页',
							),
				);
				import("siteMap.php");
				$rss = new sitemap($config);
		//$rss = spClass('sitemap',array($config));
		$db_blog = spClass('db_blog');
		$db_models = spClass('db_models');
		$models = $db_models->findAll(NULL,NULL,'id,name');
		$_models = array();
		foreach($models as $key=>$val){
			$_models[$val['id']] = $val['name'];;
		}
		$datas = $db_blog->findAll(array('open'=>1),'bid desc','title,bid,type,body,time','0,200');
		$_datas = array();
		foreach($datas as $key=>$val){
			$_datas[] = array('title' => $val['title'],
						'link' => $this->siteHost.spUrl('zshow','show',array('bid'=>$val['bid'])),
						'cate' => $_models[$val['type']],
						'desc' => $this->showBody($val['body']),
						'pubDate' => $val['time'],
						'guid' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
						);  
		}

		$rss->addAll($_datas);
		return $rss->create();
	}	

	private function showBody($body){
		$body = split_attribute(converPic($body));
		$str =  $body['body'];
		foreach($body['attr']['img'] as $b){
			$str.='<img src="'.$b['url'].'" />'.$b["desc"];
		}
		//print_r($body);exit;
		return $str;
	}
	/**
	 * bulid recommend rss
	 */
	private function recommendRss(){
		$channel = '--推荐频道';
		$_models = array();
		$config = array('flags'		=>'rss',
						'title' 	=>$this->yb['site_title'].$channel,
	 					'link'  	=>$this->siteUrl,
	 					'desc'		=>$this->yb['site_desc'].$channel,
						'lang'  	=>'zh-CN',
	 					'pubDate'	=>time(),
	 					'image'		=>array(
 									'link'	=> $this->siteUrl,
	 								'url' 	=> $this->siteUrl.'/'.$GLOBALS['G_SP']['view']['config']['template_dir'].'/image/favicon.ico',
 									'title' => $this->yb['site_title'].$channel,
									),
	 				);
		$rss = spClass('sitemap',array($config));
		$db_blog = spClass('db_blog');
		$db_models = spClass('db_models');
		$models = $db_models->findAll(NULL,NULL,'id,name');

		foreach($models as $key=>$val){
			$_models[$val['id']] = $val['name'];;
		}
		$datas = $db_blog->findAll('','','','0,20');
		$_datas = array();
		foreach($datas as $key=>$val){
			$_datas[] = array('title' => $val['title'],
						'link' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
						'cate' => $_models[$val['type']],
						'desc' => split_attribute(converPic($val['body'])),
						'pubDate' => $val['time'],
						'guid' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
						);  
		}
		$rss->addAll($_datas);
		return $rss->create();
	}
	/**
	 * bulid discovery rss
	 */
	private function discoveryRss(){
		$channel = '--发现频道';
		$config = array('flags'		=>'rss',
						'title' 	=>$this->yb['site_title'].$channel,
	 					'link'  	=>$this->siteUrl,
	 					'desc'		=>$this->yb['site_desc'].$channel,
						'lang'  	=>'zh-CN',
	 					'pubDate'	=>time(),
	 					'image'		=>array(
 									'link'	=> $this->siteUrl,
	 								'url' 	=> $this->siteUrl.'/'.$GLOBALS['G_SP']['view']['config']['template_dir'].'/image/favicon.ico',
 									'title' => $this->yb['site_title'].$channel,
									),
	 				);
		$rss = spClass('sitemap',array($config));
		$datas = $tags = spClass('db_tags')->discoverTag();
		$_datas = array();
		foreach($datas as $key=>$val){
			$_datas[] = array('title' => $val['title'],
						'link' => $this->siteHost.spUrl('blog','tag',array('tag'=>$val['title'])),
						'cate' => '发现频道',
						'desc' => $val['title'],
						'pubDate' => $val['time'],
						'guid' => $this->siteHost.spUrl('blog','tag',array('tag'=>$val['title'])),
						);  
		}
		$rss->addAll($_datas);
		return $rss->create();
	}
	/**
	 * bulid userHome rss
	 */
	private function userHomeRss(){
		if($this->spArgs('uid')){
			$uid = (int) $this->spArgs('uid');
			$cond = "and b.uid = '$uid'";
		}else{
			return;
		}
		$user = spClass('db_member')->find(array('uid'=>$uid),NULL,'username');
		$channel = '--'.$user['username'].'的个人主页';
		$config = array('flags'		=>'rss',
						'title' 	=>$this->yb['site_title'].$channel,
	 					'link'  	=>$this->siteUrl,
	 					'desc'		=>$this->yb['site_desc'].$channel,
						'lang'  	=>'zh-CN',
	 					'pubDate'	=>time(),
	 					'image'		=>array(
 									'link'	=> $this->siteUrl,
	 								'url' 	=> $this->siteUrl.'/'.$GLOBALS['G_SP']['view']['config']['template_dir'].'/image/favicon.ico',
 									'title' => $this->yb['site_title'].$channel,
									),
	 				);
		$rss = spClass('sitemap',array($config));

		$db_models = spClass('db_models');
		$models = $db_models->findAll(NULL,NULL,'id,name');
		$_models = array();
		foreach($models as $key=>$val){
			$_models[$val['id']] = $val['name'];;
		}

		$sql = "SELECT b. * , k.id AS likeid  ,m.username,m.domain
				FROM `".DBPRE."blog` AS b LEFT JOIN `".DBPRE."likes` AS k ON ( b.bid = k.bid AND k.uid ='$this->uid' )
				LEFT JOIN `".DBPRE."member`  as m on b.uid = m.uid where b.open = 1 $cond ORDER BY b.time desc limit 0,20";
		$datas = spClass('db_blog')->findSql($sql);
		$_datas = array();
		foreach($datas as $key=>$val){
			$_datas[] = array('title' => $val['title'],
						'link' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
						'cate' => $_models[$val['type']],
						'desc' => $val['body'],
						'pubDate' => $val['time'],
						'guid' => $this->siteHost.spUrl('userblog','show',array('bid'=>$val['bid'])),
						);  
		}
		$rss->addAll($_datas);
		return $rss->create();
	}
	/**
	 * bulid tag rss
	 */
	private function tagRss(){
		$channel = '--标签频道';
		$config = array('flags'		=>'rss',
						'title' 	=>$this->yb['site_title'].$channel,
	 					'link'  	=>$this->siteUrl,
	 					'desc'		=>$this->yb['site_desc'].$channel,
						'lang'  	=>'zh-CN',
	 					'pubDate'	=>time(),
	 					'image'		=>array(
 									'link'	=> $this->siteUrl,
	 								'url' 	=> $this->siteUrl.'/'.$GLOBALS['G_SP']['view']['config']['template_dir'].'/image/favicon.ico',
 									'title' => $this->yb['site_title'].$channel,
									),
	 				);
		$rss = spClass('sitemap',array($config));
		$datas = spClass('db_tags')->discoverTag();
		$db_models = spClass('db_models');
		$_datas = array();
		foreach($datas as $key=>$val){
			$_datas[] = array('title' => $val['title'],
						'link' => $this->siteHost.spUrl('blog','tag',array('tag'=>$val['title'])),
						'cate' => '标签频道',
						'desc' => $val['title'],
						'pubDate' => $val['time'],
						'guid' => $this->siteHost.spUrl('blog','tag',array('tag'=>$val['title'])),
						);  
		}
		$rss->addAll($_datas);
		return $rss->create();
	}


}
/**
 *
 * 首页 rss 显示最新的blog, http://qing.thinksaas.cn/
 *
 * 推荐 rss 显示最近登录的 用户 http://qing.thinksaas.cn/recommend
 *
 * 发现 rss 显示最热的标签 http://qing.thinksaas.cn/discovery
 *
 * 个人主页 rss 显示自己最新发布的文章 http://qing.thinksaas.cn/jackarsene
 *
 * 标签 rss 显示对应的文章 http://qing.thinksaas.cn/index.php?c=blog&a=tag&tag=%E5%89%A7%E6%83%85
 *
 *
 *	sitemap 内容 --- 放进去所有连接
 *   index
 *   recommend
 *   discovery
 *   每个人的页面
 *   每篇文章
 *   taglist
 *   自定义页面
 * 
 */