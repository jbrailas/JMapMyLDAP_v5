<?php
/**
 * PHP Version 8
 *
 * @package     Shmanic.Components
 * @subpackage  Shldap
 * @author      Shaun Maunder <shaun@shmanic.com>
 *
 * @copyright   Copyright (C) 2011-2013 Shaun Maunder. All rights reserved.
 * edited
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;


if (Joomla\CMS\Version::MAJOR_VERSION >= 4) {
	JHtml::_('behavior.formvalidator');
	JHtml::_('formbehavior.chosen', 'select');
	JHtml::_('bootstrap.tooltip');
}
else
{
	JHtml::_('behavior.formvalidation');
	JHtml::_('behavior.tooltip');
	JHtml::_('behavior.framework', true);
}


?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'host.cancel' || document.formvalidator.isValid(document.getElementById('host-form')))
		{
			if (task == 'host.debug')
			{
				return runDebug();
			}

			Joomla.submitform(task, document.getElementById('host-form'));
		}
	}

	function runDebug()
	{
		var url = 'index.php?option=com_shldap&format=raw&task=host.debug&view=host&layout=debug';

		//data = jQuery('host-form').toQueryString();
		//var formData = jQuery('#host-form').serialize();
		var formData = new FormData(jQuery('#host-form')[0]);

		// Nasty hack for correctly populating task for now
		//formData = formData.replace('task=', 'task=host.debug');


		 /* removed mootools in Joomla 4
		 Request.HTML(
			{
				url: url,
				method: 'post',
				data: data,
				onRequest: function()
				{
					$('debug-output').set('text', 'Processing...');
				},
				onSuccess: function(response)
				{
					$('debug-output').empty().adopt(response);
				},
				onFailure: function(response)
				{
					$('debug-output').set('text', 'Failed with: ' + response.responseText);
				}
			}).send();
			*/
		
		//debug
		//for (var pair of formData.entries()) {
		//	console.log(pair[0]+ ', ' + pair[1]); 
		//}

		jQuery.ajax({
			type: 'POST',
			url: url,
			data: formData,
			contentType: false,
			processData: false,
			//dataType: "json",
			success: function(response) {
				jQuery('#debug-output').empty();
				jQuery('#debug-output').html(response);
				console.log(response);
			},
			error: function(xhr, status, error) {
				console.log(xhr);
				jQuery('#debug-output').text('Failed with error: ' + error + "\n" + xhr.responseText);
			}
		});

		return true;
	}
</script>
<div id="j-sidebar-container" class="col-2">
    <?php echo $this->sidebar; ?>
</div>
<form action="<?php echo JRoute::_('index.php?option=com_shldap&layout=edit&id=' . (int) $this->id); ?>" method="post" name="adminForm" id="host-form" class="form-validate">
	<div class="row-fluid">
	<div class="width-50 fltlft span6 form-horizontal">
		<?php foreach ($this->form->getFieldSets() as $fieldset) : ?>
		<?php if ($fieldset->name !== 'debug') : ?>
		<fieldset class="adminform">
			<legend><?php echo JText::_($fieldset->label); ?></legend>
			<div class="adminformlist tab-content">
				<div class="tab-pane active" id="<?php echo $fieldset->name; ?>">
				<?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
					<?php if (!$field->hidden) : ?>
						<div class="control-group"><?php echo $field->label; ?>
						<?php echo $field->input; ?></div>
					<?php endif; ?>
				<?php endforeach; ?>
				</div>
			</div>
		</fieldset>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<div class="width-50 fltrt span6">
		<?php foreach ($this->form->getFieldSets() as $fieldset) : ?>
		<?php if ($fieldset->name == 'debug') : ?>
		<fieldset class="adminform form-vertical">
			<legend><?php echo JText::_($fieldset->label); ?></legend>
			<div class="adminformlist tab-content">
				<div class="tab-pane active" id="<?php echo $fieldset->name; ?>">

					<?php foreach ($this->form->getFieldset($fieldset->name) as $field) : ?>
						<?php if (!$field->hidden) : ?>
							<div class="control-group"><?php echo $field->label; ?>
							<?php echo $field->input; ?></div>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</fieldset>
		<?php endif; ?>
		<?php endforeach; ?>
	</div>

	<div class="width-50 fltrt span6">
		<fieldset class="adminform form-vertical">
			<legend><?php echo JText::sprintf('COM_SHLDAP_HOST_DEBUG_SECTION'); ?></legend>
			<div class="adminformlist">
				<div id="debug-output" style="width:100%;height:auto;min-height:250px;max-height:2000px;overflow:scroll;border:1px solid #bbb;background-color:#eee;">
					...
				</div>
			</div>
		</fieldset>
	</div>

	<div class="clearfix"> </div>

	<div>
		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
	</div>
</form>
