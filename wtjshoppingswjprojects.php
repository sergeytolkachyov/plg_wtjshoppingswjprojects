<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     1.1.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL 3.0
 * @since 1.0.0
 */
// No direct access
defined( '_JEXEC' ) or die;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
jimport('joomla.plugin.plugin');

class plgJshoppingorderWtjshoppingswjprojects extends JPlugin
{

	public function __construct( &$subject, $config )
	{
		parent::__construct( $subject, $config );
        $this->loadLanguage();
    }



    public function onBeforeDisplayOrderView($view){
	    JLoader::register('SWJProjectsHelperRoute', JPATH_SITE . '/components/com_swjprojects/helpers/route.php');
	    $order_view_tmp_var = $this->params->get("order_view_tmp_var");
	    $rows = $view->order->items;
	    $jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");

        foreach ($rows as $row){
				$product_id = $row->product_id;
				$order_number = $view->order->order_number;
	            $project_id = $this->getSwprojectsIdFromJshoppingProduct($product_id,$jshopping_extra_field_id);
				//SW Projects project id from JoomShopping product
				$key = $this->getSwprojectsKey($order_number,$project_id['extra_field_'.$jshopping_extra_field_id]);

	        $html = "<div class='input-group my-1'>
				        <input type='text' class='form-control form-control-sm' disabled value='".$key["key"]."'>
				      </div>";
	        $html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START")." ".date("d.m.Y",strtotime($key["date_start"]))."<br/>";

	        /*
	         * Проверка даты
	         */
				if($key["date_end"] === "0000-00-00 00:00:00"){
					$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START")." ".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START")."<br/>";
				} elseif (time() > strtotime($key["date_end"])){
					$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_ENDED")." ".date("d.m.Y",strtotime($key["date_end"]))."<br/>";
				}elseif (time() < strtotime($key["date_end"])){
					$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_END")." ".date("d.m.Y",strtotime($key["date_end"]))."<br/>";
				}

				if($this->params->get("show_note_in_order") == 1){
					$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_NOTE")." ".$key["note"];
				}

	        $downloadLink = Route::_(SWJProjectsHelperRoute::getDownloadRoute(null, $key["project_id"], $key["element"], $key["key"]));
	        $html .= "<br/><a href='".$downloadLink."' class='".$this->params->get("checkout_finish_download_btn_css_style")."'>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD")."</a>";

	            $row->$order_view_tmp_var .= $html;
			}

    }


	public function onBeforeDisplayCheckoutFinish(&$text, &$order_id)
	{
		$session = JFactory::getSession();
		$orderId = $session->get('jshop_end_order_id');
		$order = JTable::getInstance('order', 'jshop');
		$order->load($orderId);
		$order->getAllItems();
		$jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");
		$note = ""; //@todo Free attributes for Domain setting
		$show_keys_on_checkout = $this->params->get("show_key_info_on_checkout_finish");

		if($show_keys_on_checkout == 1)
		{
			$text .= "<table class='".$this->params->get("checkout_finish_key_table_css_style")."'><thead><th>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_EXTENSION_NAME")."</th><th>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_KEY")."</th><th>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_START")."</th><th>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_END")."</th><th>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD")."</th></thead><tbody>";
		}
		foreach ($order->items as $item)
		{

			$product_id = $item->product_id;
			$project_id = $this->getSwprojectsIdFromJshoppingProduct($product_id, $jshopping_extra_field_id);
			$project_id = $project_id['extra_field_' . $jshopping_extra_field_id];
			$date_start = date("Y-m-d H:i:s");
			$date_end   = (date('Y') + 1) . date('-m-d H:i:s');
			/*
			 * Get free attr value for domain name
			 */
			if(!empty($item->freeattributes)){
				$item_freeattributes = unserialize($item->freeattributes);
				$note = "";
				if($this->params->get("ask_domain") == 1 && !empty($this->params->get("jshopping_free_attr_id"))){
					$free_attr_id_for_domain = $this->params->get("jshopping_free_attr_id");
					$note = $item_freeattributes[$free_attr_id_for_domain];
				}
			}
			$key = $this->generateKey($order->order_number, $project_id, $order->email, $note, $date_start, $date_end);
			if ($show_keys_on_checkout == 1){
				$text .= "	<tr><td>" . $item->product_name . "<br/>".$note."</td><td>" . $key["key"]->key . "</td><td>" . $key["key"]->date_start . "</td><td>" . $key["key"]->date_end . "</td><td><a class='".$this->params->get("checkout_finish_download_btn_css_style")."' href='" . $key["download_link"] . "'>".Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD")."</a></td></tr>";
			}
		}
		if($show_keys_on_checkout == 1){
			$text .= "</tbody></table>";
		}
	}

	public function generateKey($order_number,$project_id,$email, $note, $date_start,$date_end)
	{
		JLoader::register('SWJProjectsHelperRoute', JPATH_SITE . '/components/com_swjprojects/helpers/route.php');
		JLoader::register('SWJProjectsHelperImages', JPATH_SITE . '/components/com_swjprojects/helpers/images.php');
		JLoader::register('SWJProjectsHelperTranslation', JPATH_ADMINISTRATOR . '/components/com_swjprojects/helpers/translation.php');

		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/components/com_swjprojects/models');
		$modelProject = BaseDatabaseModel::getInstance('Project', 'SWJProjectsModel', array('ignore_request' => false));
		if ($project = $modelProject->getItem($project_id))
		{
			JLoader::register('SWJProjectsHelperKeys', JPATH_ADMINISTRATOR . '/components/com_swjprojects/helpers/keys.php');
			Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_swjprojects/tables');
			BaseDatabaseModel::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_swjprojects/models');

			$modelKey = BaseDatabaseModel::getInstance('Key', 'SWJProjectsModel', array('ignore_request' => true));
			$data     = array(
				'key'        => '',
				'id'         => 0,
				'project_id' => $project_id,
				'order'      => $order_number,
				'email'      => $email,
				'date_start' => $date_start,
				'date_end'   => $date_end,
				'state'      => 1,
				'note'       => $note,
			);

			if ($modelKey->save($data))
			{
				$key          = $modelKey->getItem();
				$downloadLink = Route::_(SWJProjectsHelperRoute::getDownloadRoute(null, null,
					$project->element, $key->key));

				return array(
					"key" => $key,
					"download_link" => $downloadLink
				);
			}
		}
	}


	/*
	 * $product_id - int - JoomShopping product id
	 * $jshopping_extra_field_id  - int - SW Projects project id from JommShopping product extra field
	 * @return       -  str   - SW Projects project id from JShopping product
	 */

	private function getSwprojectsIdFromJshoppingProduct($product_id,$jshopping_extra_field_id){
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extra_field_'.$jshopping_extra_field_id))
			->from($db->quoteName('#__jshopping_products'))
			->where($db->quoteName('product_id') ." = ". $product_id);
		$db->setQuery($query);



		$project_id = $db->loadAssoc();
		return $project_id;
	}


	/*
	 * $order_number - str - JoomShopping order number
	 * $$project_id  - int - SW Projects project id from JommShopping product extra field
	 * @return       -  array   - SW Projects key object
	 */

	private function getSwprojectsKey($order_number, $project_id){
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.*','b.element')))
			->from($db->quoteName('#__swjprojects_keys','a'))
			->join('INNER', $db->quoteName('#__swjprojects_projects','b') . ' ON ' . $db->quoteName('a.project_id') . ' = ' . $db->quoteName('b.id'))
			->where($db->quoteName('a.order') ." = ".$order_number)
			->where($db->quoteName('a.project_id') ." = ".$project_id)
			->where($db->quoteName('a.state') ." = 1");


		/*
		 * inner join "element" from projects table where sw_project_keys.project_id = sw_projects.id
		 */
		$db->setQuery($query);
		$key = $db->loadAssoc();
		return $key;
	}
}
