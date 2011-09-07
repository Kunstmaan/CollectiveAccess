<?php

	class userImageAnnotationsPlugin extends BaseApplicationPlugin {
		# -------------------------------------------------------
		private $opo_config;
		private $ops_plugin_path;
		private $o_db;
		# -------------------------------------------------------
		public function __construct($ps_plugin_path) {
			$this->ops_plugin_path = $ps_plugin_path;
			$this->description = _t('Provides the possibility to manage annotations created by users.');
			parent::__construct();
			$this->opo_config = Configuration::load($ps_plugin_path.'/conf/userImageAnnotations.conf');
			$this->o_db = new Db();
		}
		# -------------------------------------------------------
		public function checkStatus() {
			return array(
				'description' => $this->getDescription(),
				'errors' => array(),
				'warnings' => array(),
				'available' => ((bool)$this->opo_config->get('enabled'))
			);
		}
		# -------------------------------------------------------
		/**
		 * Insert activity menu
		 */
		public function hookRenderMenuBar($pa_menu_bar) {
			if ($o_req = $this->getRequest()) {
				if (!$o_req->user->canDoAction('can_manage_user_annotations')) { return null; }
				$pa_menu_bar['manage']['navigation']['user_generated_content']['submenu']['navigation']['image_annotations'] = array(
					'displayName' => _t('Annotaties'),
					'default' => array(
						'module' => 'userImageAnnotations',
						'controller' => 'Annotations',
						'action' => 'Index'
					),
					'is_enabled' => "1",
					'requires' => array (),
					'navigation' => array(
						'moderate' => array(
							"displayName" => _("Nieuwe annotaties"),
							'default' => array(
								'module' => 'userImageAnnotations',
								'controller' => 'Annotations',
								'action' => 'Index'
							),
							"is_enabled" => 1,
							"requires" => array(),
						),
						"search" => array(
							"displayName" => _("Goedgekeurde annotaties"),
							'default' => array(
								'module' => 'userImageAnnotations',
								'controller' => 'Annotations',
								'action' => 'ListModerated'
							),
							"useActionInPath" => 1,
							"is_enabled" => 1,
							"requires" => array()
						)
					)
				);
				// die(var_dump($pa_menu_bar['manage']['navigation']['user_generated_content']['navigation']));
				return $pa_menu_bar;
			}

			return null;
		}

		# -------------------------------------------------------
		/**
		 * Add plugin user actions
		 */
		public function hookGetRoleActionList($pa_role_list) {
			$pa_role_list['plugin_userImageAnnotations'] = array(
				'label' => _t('User Image Annotations plugin'),
				'description' => _t('Actions for the image annotations plugin'),
				'actions' => array(
					'can_manage_user_annotations' => array(
						'label' => _t('Can manage the user annotations'),
						'description' => _t('User can manage the image annotations generated by users.')
					)
				)
			);

			return $pa_role_list;
		}
	}