	<?php defined('SYSPATH') or die('No direct script access.');
	/**
	 * CONTROLLER NEW 
	 */
	class Controller_New extends Controller
	{
	
	/**
	 * 
	 * NEW ADVERTISEMENT 
	 * 
	 */
	public function action_index()
	{

		//template header
		$this->template->title           	= __('Publish new advertisement');
		$this->template->meta_description	= __('Publish new advertisement');
				
		$this->template->styles 			= array('css/jquery.sceditor.min.css' => 'screen');
		//$this->template->scripts['footer'][]= 'js/autogrow-textarea.js';
		$this->template->scripts['footer'][]= 'js/jquery.sceditor.min.js';
		$this->template->scripts['footer'][]= '/js/jqBootstrapValidation.js';
		$this->template->scripts['footer'][]= 'js/pages/new.js';
		
		$category = new Model_Category();
		$location = new Model_Location();
		$user = new Model_User();
		
		//find all, for populating form select fields 
		$_cat = $category->find_all();
		$_loc = $location->find_all();
		
		$this->template->bind('content', $content);
		$this->template->content = View::factory('pages/post/new', array('_cat'		=> $_cat,
																		 '_loc' 	=> $_loc,
																		 ));

		$data = array(	'_auth' 		=> $auth 		= 	Auth::instance(),
						'title' 		=> $title 		= 	$this->request->post('title'),
						'cat'			=> $cat 		= 	$this->request->post('category'),
						'loc'			=> $loc 		= 	$this->request->post('location'),
						'description'	=> $description = 	$this->request->post('description'),
						'price'			=> $price 		= 	$this->request->post('price'),
						'address'		=> $address 	= 	$this->request->post('address'),
						'phone'			=> $phone 		= 	$this->request->post('phone'),
						'user'			=> $user = new Model_User()
						); 
		
		$config = new Model_Config();
		$config->where('group_name','=', 'general')->limit(1)->find(); // get value from config (moderation on/off)
		
		if ($config->config_value == 0){
			$status = Model_Ad::STATUS_PUBLISHED;
			$this->_save_new_ad($data, $status);

		}
		else if($config->config_value == 1)
		{
			$status = Model_Ad::STATUS_NOPUBLISHED;
			$this->_save_new_ad($data, $status);
		}
		else if($config->config_value == 2)
		{
			
			$this->template->content = View::factory('pages/post/paypal');
		}


			
 	}

 	/**
 	 * [_save_new_ad Save new advertisement if validated, with a given parameters 
 	 * 
 	 * @param  [array] $data   [post values]
 	 * @param  [int] $status [status of advert.]
 	 * 
 	 */
 	public function _save_new_ad($data, $status)
 	{
 		if (!$data['_auth']->logged_in()) // this part is for users that are not logged, not finished !!!
			{
				
				$name 		= $this->request->post('name');
				$email		= $this->request->post('email');
				$password	= $this->request->post('password');
				
				if (Valid::email($email,TRUE))
				{
					$user = $data['user']->where('email', '=', $email)
							->limit(1)
							->find();

					if ($user->loaded())
					{
						// Alert::set(Alert::SUCCESS, __('User Exists, please login first to authenticate profile'));
						// $this->request->redirect(Route::url('oc-panel',array('controller'=>'auth','action'=>'login')));
						
					}
					else
					{ 
						$user->email 	= $email;
						$user->name 	= $name;
						$user->status 	= Model_User::STATUS_ACTIVE;
						$user->id_role	= 1;//normal user
						$user->password = $this->request->post('password');	// generate new user password, bad solution find better !!!

						try
						{
							$user->save();
							Alert::set(Alert::SUCCESS, __('New profile has been created. Welcome ').$name.' !');
							// email::send("root@slobodantumanitas-System", 
							// 			"root@slobodantumanitas-System", 
							// 			"new_user",
							// 			'welcome '.$name.' with pass '.$pass,
							// 			NULL);
						}
						catch (ORM_Validation_Exception $e)
						{
							//Form::errors($content->errors);
						}
						catch (Exception $e)
						{
							throw new HTTP_Exception_500($e->getMessage());
						}
					}

						$usr = $data['user']->id_user; 
					
				}
			}
			else
			{
				$usr 		= $data['_auth']->get_user()->id_user; 		// returns and error if user not loged in !!! check that
				$name 		= $data['_auth']->get_user()->name;
				$email 		= $data['_auth']->get_user()->email;
			}	
		
		$_new_ad = ORM::factory('ad');
		
		if($this->request->post()) //post submition  
		{
		
			if(Valid::not_empty($data['title']) AND Valid::not_empty($data['description']))
			{		
				
				//insert data
				// $data['seotitle'] = $data['title'].$data['cat']; // bad solution, find better ASK CHEMA!!! 

				$seotitle = $_new_ad->gen_seo_title($data['title']); 
				
				$_new_ad->title 		= $data['title'];
				$_new_ad->id_location 	= $data['loc'];
				$_new_ad->id_category 	= $data['cat'];
				$_new_ad->id_user 		= $usr;
				$_new_ad->description 	= $data['description'];
				$_new_ad->type 	 		= '0';
				$_new_ad->seotitle 		= $seotitle;	 
				$_new_ad->status 		= $status;									// need to be 0, in production 
				$_new_ad->price 		= $data['price']; 								
				$_new_ad->adress 		= $data['address'];
				$_new_ad->phone			= $data['phone']; 
				

			   /////////////
			   // ADD capcha
			   // TO DO..
			   // //////////
			   
			    // image upload
				$error_message = NULL;
	    		$filename = NULL;
	    		
	    		if (isset($_FILES['image1']) || isset($_FILES['image2']))
	        	{
	        		$img_files = array($_FILES['image1'], $_FILES['image2']);
	            	$filename = $this->_save_image($img_files, $seotitle, $created = NULL);
	        	}
	        	if ( $filename !== TRUE)
		        {
		        	$_new_ad->has_images = 0;
		            $error_message = 'There was a problem while uploading the image.
		                Make sure it is uploaded and must be JPG/PNG/GIF file.';

		        }else $_new_ad->has_images = 1;

			try
				{
					$_new_ad->save();
					
					//$this->_send_mail($title, $name, $email, $_auth); // send mail to user
					email::send("root@slobodantumanitas-System", 
								"root@slobodantumanitas-System", 
								$name, 
								$name." has created new post with title: ".$data['title'], 
								NULL); // send to administrator , check other solution !!!   
					
					//$this->request->redirect(Route::url('default')); 
					  
				}
				catch (ORM_Validation_Exception $e)
				{
					Form::errors($content->errors);
				}
				catch (Exception $e)
				{
					throw new HTTP_Exception_500($e->getMessage());
				}
		}
		else
		{ 
			Alert::set(Alert::ALERT, __('Fields Missing'));
		}

			
			////////////////////////////////////
			// create HISTORY.xml file
			// for keeping track of changes made
			// TO DO...
			// ///////////////////////////////// 
			
			//recaptcha validation, if recaptcha active
		}
		
		$this->template->content->text = Text::bb2html($this->request->post('description'),TRUE);
 	}

 	/**
 	 * _save_image upload images with given path
 	 * 
 	 * @param  [array] 	$image    	[image $_FILE-s ]
 	 * @param  [string] $seotitle 	[unique id, and folder name]
 	 * @return [bool]           	[return true if 1 or more images uploaded, false otherwise]
 	 */
 	public function _save_image($image, $seotitle, $created)
 	{
 		$counter = 0; // count how many img fields are empty

 		foreach ($image as $image) 
 		{
 			
 			if ( 
            ! Upload::valid($image) OR
            ! Upload::not_empty($image) OR
            ! Upload::type($image, array('jpg', 'jpeg', 'png', 'gif')))
        	{
            	$image = NULL;
 			}
 			
 			if ($image !== NULL)
 			{
				$path = $this->_image_path($seotitle, $created);
		 		$directory = DOCROOT.$path;

		 		if ($file = Upload::save($image, NULL, $directory))
		        {
		        	$name = strtolower(Text::random('alnum',20));
		            $filename_big = $name.'_200x200.jpg';
		 			$filename_original = $name.'_original.jpg';
		            Image::factory($file)
		                ->resize(200, 200, Image::AUTO)
		                ->save($directory.$filename_big);
		 			
		            Image::factory($file)
		                ->save($directory.$filename_original);
		            
		            // Delete the temporary file
		            unlink($file);
		        }
		        else
		        { 
		        	return FALSE;
		        }
 			}
 			else
 			{ 
 				$counter++;
 				if ($counter > 1)
 				{
 					return FALSE;
 				}
 			}	
 		}
 		return TRUE;
    }
   	
   	/**
   	 * _image_path make unique dir path with a given date and seotitle
   	 * 
   	 * @param  [string] $seotitle 	[unique id, and folder name]
   	 * @return [string]     		[directory path]
   	 */
    public function _image_path($seotitle, $created)
    { 
    	if ($created !== NULL)
    	{
    		$obj_ad = new Model_Ad();
    		$path = $obj_ad->_gen_img_path($seotitle, $created);
    	}
    	else
    	{
    		$date = date('y/m/d');

			$parse_data = explode("/", $date); 			// make array with date values
		
			$path = "upload/"; // root upload folder

			for ($i=0; $i < count($parse_data); $i++) 
			{ 
				$path .= $parse_data[$i].'/'; 			// append, to create path 
			}
				$path .= $seotitle.'/';
		}
    	
    	

		if(!is_dir($path)){ 		// check if path exists 
				mkdir($path, 0755, TRUE);
			}

		return $path;
    }


    ///////////////////////////////
    // modify _send_email function
    // atm works but not good
    // and it needs improuvment
    ///////////////////////////
  //   public function _send_mail($title, $name, $email, $_auth){
  //   	echo 'blaasdsad';
  //   	//message format
  //       $message = "User: ".$_auth->get_user()->name." created post".PHP_EOL ;
  //       $message.= "With title : ".$title.PHP_EOL;
  //       $message.= "On date".date('d/m/Y').PHP_EOL;
  //       $subject = "User ".$_auth->get_user()->name." created new post";
		
		// if(!$_auth->logged_in()){
			
		// 	email::send("root@slobodantumanitas-System", $email, "New post by user: ".$name, $message, NULL);
		// }
		// else
		// {
		// 	$email = $_auth->get_user()->email;
		// 	email::send("root@slobodantumanitas-System", $email, $subject, $message, NULL);
		// }
  //   }
		
	}
	