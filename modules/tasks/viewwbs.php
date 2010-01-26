<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view WBS.

 File:       viewwbs.php
 Location:   pmango/modules/tasks
 Started:    2009.11.11
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2009.11.11: first stub

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
 All rights reserved.

 PMango reuses part of the code of dotProject 2.0.1: dotProject code is
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (C) 2003-2005 The dotProject Development Team.

 Other libraries used by PMango are redistributed under their own license.
 See ReadMe.txt in the root folder for details.

 PMango is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

GLOBAL $AppUI, $min_view, $m, $a;

$tasks_closed = $AppUI->getState("tasks_closed");
$tasks_opened = $AppUI->getState("tasks_opened");

$project_id = defVal( @$_GET['project_id'], 0);

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock( 'WBS', 'applet-48.png',$m, "$m.$a" );
	//$titleBlock->addCrumb( "?m=tasks", "tasks list" );
	$titleBlock->addCrumb( "?m=projects&a=view&project_id=$project_id", "View project" );
	$titleBlock->show();
}

?>

<table id='tab_settings_content' style="display: none;" border='0' cellpadding='1' cellspacing='3' align="center">
<tr>
	<td align='left' valign="top" style="border-right: solid transparent 20px;">
		<table border="0" cellspacing="0">
			<tr>
				<td class="tab_setting_title"><?php echo $AppUI->_('Show');?>:</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete'/>
					<label for="show_incomplete"><?php echo $AppUI->_('Task Names'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Alerts'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Task Time limits'); ?></label>
				</td>
			</tr>
			<tr>
		</table>
	</td>
	<td align='left' valign="top" style="border-right: solid transparent 20px;">
		<table border="0" cellspacing="0">
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Task Partecipants'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Task Progress'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Efforts and Costs'); ?></label>
				</td>
			</tr>
			<tr>
				<td class="tab_setting_title">&nbsp;</td>
				<td align="left">
					<input type='checkbox' id="show_incomplete" name='show_incomplete' />
					<label for="show_incomplete"><?php echo $AppUI->_('Task Progress'); ?></label>
				</td>
			</tr>
		</table>
	</td>
	<td valign="bottom" align="left"> <!-- FIXME this form only submit works! -->
		<input type="button" class="button" value="<?php echo $AppUI->_( 'Update' );?>"  onclick='if(compareDate(document.task_list_options.show_sdate,document.task_list_options.show_edate)) submit();'>
		<!-- FIXME adding (before submit) the needed document.task_list_options.display_opion.value="custom"; -->
	</td>
</table>

<table align='center'>
	<tr>
		<td align="center">
			<img border="0" src="./WBS1.png" />
		</td>
	</tr>
</table>



