<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     2.1.1.1
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL 3.0
 * @link https://septdir.com, https://web-tolk.ru
 * @since       1.0.0
 */

namespace Joomla\Plugin\Jshoppingorder\Wtjshoppingswjprojects\Extension;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\SWJProjects\Site\Helper\RouteHelper;

class Wtjshoppingswjprojects extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeDisplayOrderView'      => 'onBeforeDisplayOrderView',
			'onBeforeDisplayCheckoutFinish' => 'onBeforeDisplayCheckoutFinish',
		];
	}

	/**
	 *
	 *
	 * @param $view
	 *
	 *
	 * @since 1.0.0
	 */
	public function onBeforeDisplayOrderView(Event $event): void
	{
		/**
		 * @var object $view статический текст для страницы Завершения заказа из настроек JoomShopping
		 */
		[$view] = $event->getArguments();

		$order_view_tmp_var       = $this->params->get("order_view_tmp_var");
		$rows                     = $view->order->items;
		$jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");


		foreach ($rows as $row)
		{
			$product_id   = $row->product_id;
			$order_number = $view->order->order_number;
			$project_id   = $this->getSwprojectsIdFromJshoppingProduct((int) $product_id, (int) $jshopping_extra_field_id);
			if (empty($project_id))
			{
				continue;
			}

			//SW Projects project id from JoomShopping product
			$key = $this->getSwprojectsKey($order_number, $project_id);

			$html = "<div class='input-group my-1'>
				        <input type='text' class='form-control form-control-sm' disabled value='" . $key['key'] . "'>
				      </div>";
			$html .= Text::_("PLG_WTJSHOPPINGSWJPROJECTS_SINGLE_ORDER_VIEW_DATE_START") . " " . date("d.m.Y", strtotime($key["date_start"])) . "<br/>";

			/**
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

			$downloadLink = Route::_(RouteHelper::getDownloadRoute(null, $key["project_id"], $key["element"], $key['key']));
			$html         .= "<br/><a href='" . $downloadLink . "' class='" . $this->params->get("checkout_finish_download_btn_css_style") . "'>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</a>";

			$row->$order_view_tmp_var .= $html;
		}

		$event->setArgument(0, $view);

	}

	/**
	 * @param   int  $product_id                JoomShopping product id
	 * @param   int  $jshopping_extra_field_id  SW Projects project id from JommShopping product extra field
	 *
	 * @return string   SW Projects project id from JShopping product
	 */

	private function getSwprojectsIdFromJshoppingProduct(int $product_id, int $jshopping_extra_field_id): string
	{

		$db = $this->getDatabase();

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
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);
		$query->select(['a.*', 'b.element'])
			->from($db->quoteName('#__swjprojects_keys', 'a'))
			->join('INNER', $db->quoteName('#__swjprojects_projects', 'b') . ' ON ' . $db->quoteName('a.projects') . ' = ' . $db->quoteName('b.id'))
			->where($db->quoteName('a.order') . " = " . $order_number)
			->where($db->quoteName('a.projects') . " = " . $project_id)
			->where($db->quoteName('a.state') . " = 1");
		/**
		 * inner join "element" from projects table where sw_project_keys.project_id = sw_projects.id
		 */
		$db->setQuery($query);
		$keys = $db->loadAssoc();

		return $keys;
	}

	public function onBeforeDisplayCheckoutFinish(Event $event): void
	{
		/**
		 * @var string $text     статический текст для страницы Завершения заказа из настроек JoomShopping
		 * @var int    $order_id id заказа
		 */
		[$text, $order_id] = $event->getArguments();

		$order = JSFactory::getTable('order', 'jshop');
		$order->load($order_id);
		$order->getAllItems();
		$jshopping_extra_field_id = $this->params->get("jshopping_extra_field_id");
		$show_keys_on_checkout    = $this->params->get("show_key_info_on_checkout_finish");

		$user_info = JSFactory::getUser();
		$user_id   = (int) $user_info->user_id;

		$order_status_comment    = '';
		$table_rows              = '';
		$has_swjproject_projects = false;

		foreach ($order->items as $item)
		{

			$product_id = $item->product_id;
			$project_id = (int) $this->getSwprojectsIdFromJshoppingProduct((int) $product_id, (int) $jshopping_extra_field_id);

			if (empty($project_id))
			{
				continue;
			}
			$has_swjproject_projects = true;
			$date_start              = new Date('now');
			$date_start->format('Y-m-d');
			$date_start->setTime('00', '00', '00');
			$date_end = new Date('now +1 year');
			$date_end->format('Y-m-d');
			$date_end->setTime('23', '59', '59');

			/**
			 * Get free attr value for domain name
			 */
			if (!empty($item->freeattributes))
			{
				$item_freeattributes = unserialize($item->freeattributes);
				$note                = '';
				if ($this->params->get("ask_domain") == 1 && !empty($this->params->get("jshopping_free_attr_id")))
				{
					$free_attr_id_for_domain = $this->params->get("jshopping_free_attr_id");
					$note                    = trim($item_freeattributes[$free_attr_id_for_domain]);
					$note                    = (new Uri($note))->setScheme('https')->toString();
				}
			}
			$key = $this->generateKey((int) $order->order_number, $project_id, $order->email, $note, (string) $date_start, (string) $date_end, $user_id);
			if ($show_keys_on_checkout == 1)
			{
				$table_rows   .= "	<tr><td>" . $item->product_name . "<br/>" . $note . "</td><td>" . $key['key']->key . "</td><td>" . $key['key']->date_start . "</td><td>" . $key['key']->date_end . "</td><td><a class='" . $this->params->get("checkout_finish_download_btn_css_style") . "' href='" . $key["download_link"] . "'>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</a></td></tr>";
				$download_url = new Uri(Uri::root());
				$download_url->setPath($key["download_link"]);
				$project_link         = HTMLHelper::link($download_url->toString(), Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD"));
				$order_status_comment .= Text::_('PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_START') . ': ' . $key['key']->date_start . ', ' . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_END") . ': ' . $key['key']->date_end . ', ' . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_KEY") . ': ' . $key['key']->key . ', ' . $project_link;
			}

		}

		if ($has_swjproject_projects)
		{
			if ($show_keys_on_checkout == 1)
			{
				$text .= "<table class='" . $this->params->get("checkout_finish_key_table_css_style") . "'><thead><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_EXTENSION_NAME") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_KEY") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_START") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DATE_END") . "</th><th>" . Text::_("PLG_WTJSHOPPINGSWJPROJECTS_CHECKOUT_FINISH_VIEW_DOWNLOAD") . "</th></thead><tbody>";
				$text .= $table_rows;
				$text .= "</tbody></table>";
				$event->setArgument(0, $text);
			}

			$this->updateJShoppingOrderHistory($order->order_status, $order_id, $order_status_comment);
		}
	}


	/**
	 * Generate download key for SW JProejects project
	 *
	 * @param   int     $order_number
	 * @param   int     $project_id
	 * @param   string  $email
	 * @param   string  $note
	 * @param   string  $date_start
	 * @param   string  $date_end
	 * @param   int     $user_id
	 *
	 * @return array|void
	 *
	 * @since 2.1.0
	 */
	private function generateKey(int $order_number, int $project_id, string $email, string $note, string $date_start, string $date_end, int $user_id = 0): array
	{
		$modelProject = $this->getApplication()
			->bootComponent('com_swjprojects')
			->getMVCFactory()
			->createModel('Project', 'Site', array('ignore_request' => false));
		$keyData      = [];
		if ($project = $modelProject->getItem($project_id))
		{
			$modelKey = $this->getApplication()
				->bootComponent('com_swjprojects')
				->getMVCFactory()
				->createModel('Key', 'Administrator', ['ignore_request' => true]);
			$data     = [
				'key'        => '',
				'id'         => 0,
				'projects'   => [$project_id],
				'order'      => $order_number,
				'email'      => $email,
				'date_start' => $date_start,
				'date_end'   => $date_end,
				'state'      => 1,
				'note'       => $note,
				'domain'     => $note,
			];

			if ($user_id > 0)
			{
				$data['user'] = $user_id;
			}

			if ($modelKey->save($data))
			{
				$key          = $modelKey->getItem();
				$downloadLink = Route::_(RouteHelper::getDownloadRoute(null, $project_id,
					$project->element, $key->key));

				$keyData['key']           = $key;
				$keyData['download_link'] = $downloadLink;
			}
		}

		return $keyData;
	}

	/**
	 * Function for updating JoomShopping order history
	 *
	 * @param   string  $jshopping_order_status_id
	 * @param   string  $jshopping_order_id
	 * @param   string  $additional_text  Additional text from plugin's settings for order history
	 *
	 * @since    1.2.0
	 */
	private function updateJShoppingOrderHistory($jshopping_order_status_id, $jshopping_order_id, $additional_text = null)
	{
		if (!empty($jshopping_order_id))
		{
			$orderChangeStatusModel = JSFactory::getModel('orderChangeStatus', 'jshop');
			$orderChangeStatusModel->setData($jshopping_order_id, $jshopping_order_status_id, 1, $jshopping_order_status_id, 1, $additional_text, 1, 0);
			$orderChangeStatusModel->store();
		}
	}
}
