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
namespace Joomla\Plugin\Jshoppingorder\Wtjshoppingswjprojects\Fields;

defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;

FormHelper::loadFieldClass('list');

class JshoppingfreeattrField extends ListField
{

	protected $type = 'jshoppingfreeattr';

	protected function getOptions(): array
	{
		if (!file_exists((JPATH_SITE . '/components/com_jshopping/bootstrap.php')))
		{
			return ['-- JoomShopping component is not installed -- '];
		}

		require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
		$options = [];
		$freeattributes = JSFactory::getTable('freeattribut','Joomla\\Component\\Jshopping\\Site\\Table\\');
		$namesfreeattributes = $freeattributes->getAllNames();

		$default = $this->element['default'] ?? null;
		if (isset($default) && ((string)$default) === '') {
			$options[] = HTMLHelper::_('select.option', '', '');
		}
		foreach ($namesfreeattributes as $attr_id => $attr_name) {
			$options[] = HTMLHelper::_('select.option', $attr_id, $attr_name);
		}

		return $options;
	}
}