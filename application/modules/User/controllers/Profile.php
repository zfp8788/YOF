<?php

class ProfileController extends BasicController {

	private $m_user;

	private function init(){
		$this->m_user = $this->load('user');
		$userID = $this->getSession('userID');

		if($userID){
			define('USER_ID', $userID);
		}
	}

	public function indexAction(){
		$m_article = $this->load('Article');
        $userID = $this->getSession('userID');

        if($userID){
            $buffer['username'] = $this->getSession('username');

            // User Aritcles
            $where = array('userID' => USER_ID);
            $total = $m_article->Where($where)->Total();

            $page = $this->get('page');

            $pages = ceil($total/10);
            $order = array('addTime' => 'DESC');
            $limit = $this->getLimit();

            $url = '/user/profile';
            $buffer['pageNav']  = generatePageLink($page, $pages, $url, $total);
            $buffer['articles'] = $m_article->Where($where)->Order($order)->Limit($limit)->Select();
        }else{
        	$this->redirect('/');
        }

        $this->getView()->assign($buffer);
	}

	// Logout
	public function logoutAction(){
		$this->unsetSession('userID');
		$this->unsetSession('username');

		$this->redirect('/');
	}

	// Profile
	public function editAction(){
		$buffer['user'] = $this->m_user->SelectByID('', USER_ID);

		$provinceID = $buffer['user']['provinceID'];
		$cityID = $buffer['user']['cityID'];
		$regionID = $buffer['user']['regionID'];

		$l_city = new City();

		$buffer['cityElement'] = $l_city->generateCityElement($provinceID, $cityID, $regionID, 1);
		$this->getView()->assign($buffer);
	}
	
	public function profileActAction(){
		$m['realname']   = $this->getPost('realname');
		$m['provinceID'] = $this->getPost('areaProvince');
		$m['cityID']     = $this->getPost('areaCity');
		$m['regionID']   = $this->getPost('areaRegion');

		$m['province'] = $this->load('Province')->getProvinceNameByID($m['provinceID']);
		$m['city']     = $this->load('City')->getCityNameByID($m['cityID']);
		if($m['regionID']){
			$m['region'] = $this->load('Region')->getRegionNameByID($m['regionID']);
		}

		$code = $this->m_user->UpdateByID($m, USER_ID);

		// Upload avatar if selected
		if($_FILES['avatar']['name']){
            $fileName = CUR_TIMESTAMP;
            $up = new Upload($_FILES['avatar'], UPLOAD_PATH.'/');
            $result = $up->upload($fileName);

            if($result == 1){
            	$m['avatar'] = $fileName.'.'.$up->extension;
            	$this->m_user->UpdateByID($m, USER_ID);
            }else{
            	jsAlert($result);
            }
        }
		
		if(FALSE === $code && $result != 1){
			jsAlert('编辑个人信息失败, 请重试');
		}

		$this->redirect('/user/profile/edit');
	}

	// 二维码
	public function qrcodeAction(){
		$value = $this->get('value', FALSE);
		if($value){
	    	$savePath = APP_PATH.'/public/qrcode';

	    	if(!file_exists($savePath)){
	    		Helper::import('File');
	    		createRDir($savePath);
	    	}

	    	$err  = 'L';
			$size = '10';
			// 有 LOGO 的话去掉下一行的注释, 并作为构造函数的第五个参数传入
			//$logo = APP_PATH.'/asset/logo.jpg';

			Helper::import('String');
			$file = getRandom(6, 1).'.png';
	        $qr = $savePath.'/'.$file;

	        $Qrcode = new myQrcode($value, $qr, $err, $size);
			$Qrcode->createQr();

			$buffer['qrCode'] = '/qrcode/'.$file;
		}

		if(isset($buffer)){
			$this->getView()->assign($buffer);
		}
	}

	// Uploadify
	public function uploadifyAction(){
		
	}

	// 省市区三级联动
	public function cityAction(){
		$l_city = new City();

		$buffer['cityElement'] = $l_city->generateCityElement(SITE_PROVINCE, SITE_CITY, SITE_REGION, 1);
		$this->getView()->assign($buffer);
	}

	// 层级式省市区三级联动
	public function cityPopAction(){
		$l_city = new City();

		$buffer['cityElement'] = $l_city->generatePopCityElement('', 3);
		$this->getView()->assign($buffer);
	}

	// URL Rewrite
	public function rewriteAction(){
		$buffer['articles'] = $this->load('Article')
								->Order('id DESC')
								->Limit(4)
								->Select();
		$this->getView()->assign($buffer);
	}

	public function renderAction(){
		
	}

	// 实现类似 Smarty 的 fetch功能
	public function renderAjaxAction(){
		$m_article = $this->load('Article');
		$where = array('userID' => USER_ID);
		$buffer['articles'] = $m_article->Where($where)
										->Order(array('id' => 'DESC'))
										->Limit(10)
										->Select();

		$this->getView()->assign($buffer);
		$content = $this->render('renderAjax');
		echo $content; die;
	}

	// 演示自定义错误之加载不存在的函数
	public function functionErrorAction(){
		Helper::import('NB');
	}

	// 演示自定义错误之访问不存的方法
	public function actionErrorAction(){
		$url = 'http://yof.mylinuxer.com/article/abc?pd=1';
		jsRedirect($url);
	}

	// 演示自定义错误之加载不存在的类	
	public function libraryErrorAction(){
		$l_nb = new NB();
	}

	// 演示自定义错误之MySQL 报错
	// 最后一个 SQL Tab 输出扫行出错的 SQL 
	public function mysqlErrorAction(){
		// 故意让列错误
		$where = array('userID_xxxxx' => 5);
        $order = array('addTime' => 'DESC');
        $limit = $this->getLimit();

        $buffer['articles'] = $this->load('Article')->Where($where)->Order($order)->Limit($limit)->Select();
	}
}