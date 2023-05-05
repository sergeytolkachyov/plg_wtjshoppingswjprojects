<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     2.0.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL 3.0
 * @link https://septdir.com, https://web-tolk.ru
 * @since       1.0.0
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;

class plgJshoppingorderWtjshoppingswjprojects extends CMSPlugin
{

	protected $autoloadLanguage = true;

	public function onBeforeDisplayOrderView($view)
	{
		JLoader::register('SWJProjectsHelperRoute', JPATH_SITE . '/components/com_swjprojects/helpers/route.php');
		$order_view_tmp_var       = $this->params->get("order_view_tmp_var");
		$rows                     = $view->order->items;
		$jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");


		foreach ($rows as $row)
		{
			$product_id   = $row->product_id;
			$order_number = $view->order->order_number;
			$project_id   = $this->getSwprojectsIdFromJshoppingProduct((int) $product_id, (int) $jshopping_extra_field_id);

			//SW Projects project id from JoomShopping product
			$key = $this->getSwprojectsKey($order_number, $project_id);

			$html = "<div class='input-group my-1'>
				        <input type='text' class='form-control form-control-sm' disabled value='" . $key["key"] . "'>
				      </div>";
			$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START") . " " . date("d.m.Y", strtotime($key["date_start"])) . "<br/>";

			/*
			 * Проверка даты
			 */

			if ($key["date_end"] === "0000-00-00 00:00:00")
			{
				$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START") . " " . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START") . "<br/>";
			}
			elseif (time() > strtotime($key["date_end"]))
			{
				$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_ENDED") . " " . date("d.m.Y", strtotime($key["date_end"])) . "<br/>";
			}
			elseif (time() < strtotime($key["date_end"]))
			{
				$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_END") . " " . date("d.m.Y", strtotime($key["date_end"])) . "<br/>";
			}

			if ($this->params->get("show_note_in_order") == 1)
			{
				$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_NOTE") . " " . $key["note"];
			}

			$downloadLink = Route::_(SWJProjectsHelperRoute::getDownloadRoute(null, $key["project_id"], $key["element"], $key["key"]));
			$html         .= "<br/><a href='" . $downloadLink . "' class='" . $this->params->get("checkout_finish_download_btn_css_style") . "'>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</a>";

			$row->$order_view_tmp_var .= $html;
		}

	}


	public function onBeforeDisplayCheckoutFinish(&$text, &$order_id)
	{
		$order = \JSFactory::getTable('order', 'jshop');
		$order->load($order_id);
		$order->getAllItems();
		$jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");
		$show_keys_on_checkout    = $this->params->get("show_key_info_on_checkout_finish");

		$user_info = \Joomla\Component\Jshopping\Site\Lib\JSFactory::getUser();
		$user_id   = $user_info->user_id;

		if ($show_keys_on_checkout == 1)
		{
			$text .= "<table class='" . $this->params->get("checkout_finish_key_table_css_style") . "'><thead><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_EXTENSION_NAME") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_KEY") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_START") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_END") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</th></thead><tbody>";
		}

		$order_status_comment = '';

		foreach ($order->items as $item)
		{

			$product_id = $item->product_id;
			$project_id = $this->getSwprojectsIdFromJshoppingProduct((int) $product_id, (int) $jshopping_extra_field_id);

			$date_start = new Date('now');
			$date_start->format('Y-m-d');
			$date_end = new Date('now +1 year');
			$date_end->format('Y-m-d');

			/**
			 * Get free attr value for domain name
			 */
			if (!empty($item->freeattributes))
			{
				$item_freeattributes = unserialize($item->freeattributes);
				$note                = "";
				if ($this->params->get("ask_domain") == 1 && !empty($this->params->get("jshopping_free_attr_id")))
				{
					$free_attr_id_for_domain = $this->params->get("jshopping_free_attr_id");
					$note                    = $item_freeattributes[$free_attr_id_for_domain];
				}
			}
			$key = $this->generateKey($order->order_number, $project_id, $order->email, $note, (string) $date_start, (string) $date_end, $user_id);
			if ($show_keys_on_checkout == 1)
			{
				$text                 .= "	<tr><td>" . $item->product_name . "<br/>" . $note . "</td><td>" . $key["key"]->key . "</td><td>" . $key["key"]->date_start . "</td><td>" . $key["key"]->date_end . "</td><td><a class='" . $this->params->get("checkout_finish_download_btn_css_style") . "' href='" . $key["download_link"] . "'>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</a></td></tr>";
				$order_status_comment .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_START") . ': ' . $key["key"]->date_start . ', ' . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_END") . ': ' . $key["key"]->date_end . ', ' . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_KEY") . ': ' . $key["key"]->key . ', <a href="' . rtrim(JUri::root(), '/') . $key["download_link"] . '">' . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . '</a>';
			}
		}
		if ($show_keys_on_checkout == 1)
		{
			$text .= "</tbody></table>";
		}

		$this->updateJShoppingOrderHistory($order->order_status, $order_id, $order_status_comment);


	}

	public function generateKey($order_number, $project_id, $email, $note, $date_start, $date_end, $user_id = 0)
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
				'projects'   => [$project_id],
				'order'      => $order_number,
				'email'      => $email,
				'date_start' => $date_start,
				'date_end'   => $date_end,
				'state'      => 1,
				'note'       => $note,
			);

			if ($user_id > 0)
			{
				$data['user'] = $user_id;
			}

			if ($modelKey->save($data))
			{
				$key          = $modelKey->getItem();
				$downloadLink = Route::_(SWJProjectsHelperRoute::getDownloadRoute(null, null,
					$project->element, $key->key));

				return array(
					"key"           => $key,
					"download_link" => $downloadLink
				);
			}
		}
	}


	/**
	 * @param   int  $product_id                JoomShopping product id
	 * @param   int  $jshopping_extra_field_id  SW Projects project id from JommShopping product extra field
	 *
	 * @return string   SW Projects project id from JShopping product
	 */

	private function getSwprojectsIdFromJshoppingProduct(int $product_id, int $jshopping_extra_field_id): string
	{

		$db = Factory::getContainer()->get('DatabaseDriver');

		$query = $db->getQuery(true);
		$query->select($db->quoteName('extra_field_' . $jshopping_extra_field_id))
			->from($db->quoteName('#__jshopping_products_to_extra_fields'))
			->where($db->quoteName('product_id') . " = " . $product_id);
		$db->setQuery($query);


		$project_id = $db->loadResult();

		return $project_id;
	}


	/**
	 * @param   string  $order_number  JoomShopping order number
	 * @param   int     $project_id    SW Projects project id from JommShopping product extra field
	 *
	 * @return array SW Projects key array
	 */

	private function getSwprojectsKey($order_number, $project_id)
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.*', 'b.element')))
			->from($db->quoteName('#__swjprojects_keys', 'a'))
			->join('INNER', $db->quoteName('#__swjprojects_projects', 'b') . ' ON ' . $db->quoteName('a.projects') . ' = ' . $db->quoteName('b.id'))
			->where($db->quoteName('a.order') . " = " . $order_number)
			->where($db->quoteName('a.projects') . " = " . $project_id)
			->where($db->quoteName('a.state') . " = 1");


		/*
		 * inner join "element" from projects table where sw_project_keys.project_id = sw_projects.id
		 */
		$db->setQuery($query);
		$keys = $db->loadAssoc();

		return $keys;
	}

	/**
	 * Function for updating JoomShopping order history
	 *
	 * @param $jshopping_order_status_id    string
	 * @param $jshopping_order_id           string
	 * @param $additional_text              string  Additional text from plugin's settings for order history
	 *
	 * @since    1.2.0
	 */
	private function updateJShoppingOrderHistory($jshopping_order_status_id, $jshopping_order_id, $additional_text = null)
	{
		if (!empty($jshopping_order_id))
		{
			$orderChangeStatusModel = \JSFactory::getModel('orderChangeStatus', 'jshop');
			$orderChangeStatusModel->setData($jshopping_order_id, $jshopping_order_status_id, 1, $jshopping_order_status_id, 1, $additional_text, 1, 0);
			$orderChangeStatusModel->store();
		}
	}//updateJShoppingOrderHistory
}
