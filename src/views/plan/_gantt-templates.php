<?php

use rmrevin\yii\fontawesome\FAS;

/* @var $this \yii\web\View */

//echo FAS::i('angle-left');
//echo FAS::i('dot-circle');
//echo FAS::i('angle-right');
?>

<div id="gantEditorTemplates" style="display:none;">
    <div class="__template__" type="GANTBUTTONS">
        <!--
        <div class="ganttButtonBar noprint d-print-none btn-toolbar my-2" role="toolbar" aria-label="Gantt toolbar">
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('undo.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite" title="undo">
                    <?= FAS::i('undo'); ?>
                </button>
                <button onclick="$('#gantt').trigger('redo.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite" title="redo">
                    <?= FAS::i('redo'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('addAboveCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanAdd" title="insert above">
                    <?= FAS::i('level-up-alt'); ?>
                </button>
                <button onclick="$('#gantt').trigger('addBelowCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanAdd" title="insert below">
                    <?= FAS::i('level-down-alt'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('outdentCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanInOutdent" title="un-indent task">
                    <?= FAS::i('long-arrow-alt-left'); ?>
                </button>
                <button onclick="$('#gantt').trigger('indentCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanInOutdent" title="indent task">
                    <?= FAS::i('long-arrow-alt-right'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('moveUpCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanMoveUpDown" title="move up">
                    <?= FAS::i('long-arrow-alt-up'); ?>
                </button>
                <button onclick="$('#gantt').trigger('moveDownCurrentTask.gantt'); return false;"
                        class="btn btn-secondary requireCanWrite requireCanMoveUpDown" title="move down">
                    <?= FAS::i('long-arrow-alt-down'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('deleteFocused.gantt'); return false;"
                        class="btn btn-secondary delete requireCanWrite" title="Elimina">
                    <?= FAS::i('trash-alt'); ?>
                </button>
                <span class="ganttButtonSeparator"></span>
                <button onclick="$('#gantt').trigger('expandAll.gantt'); return false;" class="btn btn-secondary"
                        title="EXPAND_ALL">
                    <?= FAS::i('expand-alt'); ?>
                </button>
                <button onclick="$('#gantt').trigger('collapseAll.gantt'); return false;" class="btn btn-secondary"
                        title="COLLAPSE_ALL">
                    <?= FAS::i('compress-alt'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('zoomMinus.gantt'); return false;" class="btn btn-secondary"
                        title="zoom out">
                    <?= FAS::i('search-minus'); ?>
                </button>
                <button onclick="$('#gantt').trigger('zoomPlus.gantt'); return false;" class="btn btn-secondary"
                        title="zoom in">
                    <?= FAS::i('search-plus'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('print.gantt'); return false;" class="btn btn-secondary"
                        title="Print">
                    <?= FAS::i('print'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button
                    onclick="window.gantt.gantt.showCriticalPath=!window.gantt.gantt.showCriticalPath; gantt.redraw(); return false;"
                    class="btn btn-secondary requireCanSeeCriticalPath" title="CRITICAL_PATH">
                    <?= FAS::i('share-alt'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="window.gantt.splitter.resize(.1); return false;" class="btn btn-secondary">
                    <?= FAS::i('project-diagram'); ?>
                </button>
                <button onclick="window.gantt.splitter.resize(50); return false;" class="btn btn-secondary">
                    <?= FAS::i('window-restore'); ?>
                </button>
                <button onclick="window.gantt.splitter.resize(100); return false;" class="btn btn-secondary">
                    <?= FAS::i('table'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="$('#gantt').trigger('fullScreen.gantt'); return false;" class="btn btn-secondary"
                        title="FULLSCREEN" id="fullscrbtn">
                    <?= FAS::i('expand'); ?>
                </button>
                <button onclick="window.gantt.element.toggleClass('colorByStatus' ); return false;" class="btn btn-secondary">
                    <?= FAS::i('palette'); ?>
                </button>
                <button onclick="editResources();" class="btn btn-secondary requireWrite" title="edit resources">
                    <?= FAS::i('users'); ?>
                </button>
            </div>
            <div class="btn-group mr-2">
                <button onclick="saveGanttOnServer();" class="btn btn-success requireWrite" title="Save">Save</button>
                <button onclick="window.gantt.reset();" class="btn btn-secondary requireWrite newproject"><em>clear project</em></button>
            </div>
        </div>
        -->
    </div>

    <div class="__template__" type="TASKSEDITHEAD">
        <!--
        <table class="gdfTable table table-bordered table-sm" cellspacing="0" cellpadding="0">
            <thead class="thead-dark">
                <tr style="height:40px">
                    <th class="gdfColHeader" style="width:35px; border-right: none"></th>
                    <th class="gdfColHeader" style="width:25px;"></th>
                    <th class="gdfColHeader gdfResizable" style="width:100px;">code/short name</th>
                    <th class="gdfColHeader gdfResizable" style="width:300px;"><?= Yii::t('simialbi/kanban/model/task', 'Subject'); ?></th>
                    <th class="gdfColHeader" align="center" style="width:17px;" title="Start date is a milestone.">
                        <span class="teamworkIcon" style="font-size: 8px;">^</span>
                    </th>
                    <th class="gdfColHeader gdfResizable" style="width:80px;"><?= Yii::t('simialbi/kanban/model/task', 'Start date'); ?></th>
                    <th class="gdfColHeader" align="center" style="width:17px;" title="End date is a milestone.">
                        <span class="teamworkIcon" style="font-size: 8px;">^</span>
                    </th>
                    <th class="gdfColHeader gdfResizable" style="width:80px;"><?= Yii::t('simialbi/kanban/model/task', 'End date'); ?></th>
                    <th class="gdfColHeader gdfResizable" style="width:50px;">dur.</th>
                    <th class="gdfColHeader gdfResizable" style="width:20px;">%</th>
                    <th class="gdfColHeader gdfResizable requireCanSeeDep" style="width:50px;"><?= Yii::t('simialbi/kanban/task', 'Dependencies') ?></th>
                    <th class="gdfColHeader gdfResizable" style="width:1000px; text-align: left; padding-left: 10px;">
                        <?= Yii::t('simialbi/kanban/plan', 'assignee') ?>
                    </th>
                </tr>
            </thead>
        </table>
        -->
    </div>

    <div class="__template__" type="TASKROW">
        <!--
        <tr id="tid_(#=obj.id#)" taskId="(#=obj.id#)"
            class="taskEditRow (#=obj.isParent()?'isParent':''#) (#=obj.collapsed?'collapsed':''#)" level="(#=level#)">
            <th class="gdfCell edit" align="right" style="cursor:pointer;"><span class="taskRowIndex">(#=obj.getRow()+1#)</span>
                <span class="teamworkIcon" style="font-size:12px;">e</span></th>
            <td class="gdfCell noClip" align="center">
                <div class="taskStatus cvcColorSquare" status="(#=obj.status#)"></div>
            </td>
            <td class="gdfCell"><input type="text" name="code" value="(#=obj.code?obj.code:''#)"
                                       placeholder="code/short name"></td>
            <td class="gdfCell indentCell" style="padding-left:(#=obj.level*10+18#)px;">
                <div class="exp-controller" align="center"></div>
                <input type="text" name="name" value="(#=obj.name#)" placeholder="name">
            </td>
            <td class="gdfCell" align="center"><input type="checkbox" name="startIsMilestone"></td>
            <td class="gdfCell"><input type="text" name="start" value="" class="date"></td>
            <td class="gdfCell" align="center"><input type="checkbox" name="endIsMilestone"></td>
            <td class="gdfCell"><input type="text" name="end" value="" class="date"></td>
            <td class="gdfCell"><input type="text" name="duration" autocomplete="off" value="(#=obj.duration#)"></td>
            <td class="gdfCell"><input type="text" name="progress" class="validated" entrytype="PERCENTILE"
                                       autocomplete="off" value="(#=obj.progress?obj.progress:''#)"
                                       (#=obj.progressByWorklog?"readOnly":""#)></td>
            <td class="gdfCell requireCanSeeDep"><input type="text" name="depends" autocomplete="off"
                                                        value="(#=obj.depends#)" (#=obj.hasExternalDep?"readonly":""#)>
            </td>
            <td class="gdfCell taskAssigs">(#=obj.getAssigsString()#)</td>
        </tr>
        -->
    </div>

    <div class="__template__" type="TASKEMPTYROW">
        <!--
        <tr class="taskEditRow emptyRow">
            <th class="gdfCell" align="right"></th>
            <td class="gdfCell noClip" align="center"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell"></td>
            <td class="gdfCell requireCanSeeDep"></td>
            <td class="gdfCell"></td>
        </tr>
        -->
    </div>

    <div class="__template__" type="TASKBAR"><!--
  <div class="taskBox taskBoxDiv" taskId="(#=obj.id#)" >
    <div class="layout (#=obj.hasExternalDep?'extDep':''#)">
      <div class="taskStatus" status="(#=obj.status#)"></div>
      <div class="taskProgress" style="width:(#=obj.progress>100?100:obj.progress#)%; background-color:(#=obj.progress>100?'red':'rgb(153,255,51);'#);"></div>
      <div class="milestone (#=obj.startIsMilestone?'active':''#)" ></div>

      <div class="taskLabel"></div>
      <div class="milestone end (#=obj.endIsMilestone?'active':''#)" ></div>
    </div>
  </div>
  --></div>


    <div class="__template__" type="CHANGE_STATUS"><!--
    <div class="taskStatusBox">
    <div class="taskStatus cvcColorSquare" status="STATUS_ACTIVE" title="Active"></div>
    <div class="taskStatus cvcColorSquare" status="STATUS_DONE" title="Completed"></div>
    <div class="taskStatus cvcColorSquare" status="STATUS_FAILED" title="Failed"></div>
    <div class="taskStatus cvcColorSquare" status="STATUS_SUSPENDED" title="Suspended"></div>
    <div class="taskStatus cvcColorSquare" status="STATUS_WAITING" title="Waiting" style="display: none;"></div>
    <div class="taskStatus cvcColorSquare" status="STATUS_UNDEFINED" title="Undefined"></div>
    </div>
  --></div>


    <div class="__template__" type="TASK_EDITOR"><!--
  <div class="ganttTaskEditor">
    <h2 class="taskData">Task editor</h2>
    <table  cellspacing="1" cellpadding="5" width="100%" class="taskData table" border="0">
          <tr>
        <td width="200" style="height: 80px"  valign="top">
          <label for="code">code/short name</label><br>
          <input type="text" name="code" id="code" value="" size=15 class="formElements" autocomplete='off' maxlength=255 style='width:100%' oldvalue="1">
        </td>
        <td colspan="3" valign="top"><label for="name" class="required">name</label><br><input type="text" name="name" id="name"class="formElements" autocomplete='off' maxlength=255 style='width:100%' value="" required="true" oldvalue="1"></td>
          </tr>


      <tr class="dateRow">
        <td nowrap="">
          <div style="position:relative">
            <label for="start">start</label>&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="checkbox" id="startIsMilestone" name="startIsMilestone" value="yes"> &nbsp;<label for="startIsMilestone">is milestone</label>&nbsp;
            <br><input type="text" name="start" id="start" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
            <span title="calendar" id="starts_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>          </div>
        </td>
        <td nowrap="">
          <label for="end">End</label>&nbsp;&nbsp;&nbsp;&nbsp;
          <input type="checkbox" id="endIsMilestone" name="endIsMilestone" value="yes"> &nbsp;<label for="endIsMilestone">is milestone</label>&nbsp;
          <br><input type="text" name="end" id="end" size="8" class="formElements dateField validated date" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DATE">
          <span title="calendar" id="ends_inputDate" class="teamworkIcon openCalendar" onclick="$(this).dateField({inputField:$(this).prevAll(':input:first'),isSearchField:false});">m</span>
        </td>
        <td nowrap="" >
          <label for="duration" class=" ">Days</label><br>
          <input type="text" name="duration" id="duration" size="4" class="formElements validated durationdays" title="Duration is in working days." autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="DURATIONDAYS">&nbsp;
        </td>
      </tr>

      <tr>
        <td  colspan="2">
          <label for="status" class=" ">status</label><br>
          <select id="status" name="status" class="taskStatus" status="(#=obj.status#)"  onchange="$(this).attr('STATUS',$(this).val());">
            <option value="STATUS_ACTIVE" class="taskStatus" status="STATUS_ACTIVE" >active</option>
            <option value="STATUS_WAITING" class="taskStatus" status="STATUS_WAITING" >suspended</option>
            <option value="STATUS_SUSPENDED" class="taskStatus" status="STATUS_SUSPENDED" >suspended</option>
            <option value="STATUS_DONE" class="taskStatus" status="STATUS_DONE" >completed</option>
            <option value="STATUS_FAILED" class="taskStatus" status="STATUS_FAILED" >failed</option>
            <option value="STATUS_UNDEFINED" class="taskStatus" status="STATUS_UNDEFINED" >undefined</option>
          </select>
        </td>

        <td valign="top" nowrap>
          <label>progress</label><br>
          <input type="text" name="progress" id="progress" size="7" class="formElements validated percentile" autocomplete="off" maxlength="255" value="" oldvalue="1" entrytype="PERCENTILE">
        </td>
      </tr>

          </tr>
          <tr>
            <td colspan="4">
              <label for="description">Description</label><br>
              <textarea rows="3" cols="30" id="description" name="description" class="formElements" style="width:100%"></textarea>
            </td>
          </tr>
        </table>

    <h2>Assignments</h2>
  <table  cellspacing="1" cellpadding="0" width="100%" id="assigsTable">
    <tr>
      <th style="width:100px;">name</th>
      <th style="width:70px;">Role</th>
      <th style="width:30px;">est.wklg.</th>
      <th style="width:30px;" id="addAssig"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
    </tr>
  </table>

  <div style="text-align: right; padding-top: 20px">
    <span id="saveButton" class="button first" onClick="$(this).trigger('saveFullEditor.gantt');">Save</span>
  </div>

  </div>
  --></div>


    <div class="__template__" type="ASSIGNMENT_ROW"><!--
  <tr taskId="(#=obj.task.id#)" assId="(#=obj.assig.id#)" class="assigEditRow" >
    <td ><select name="resourceId"  class="formElements" (#=obj.assig.id.indexOf("tmp_")==0?"":"disabled"#) ></select></td>
    <td ><select type="select" name="roleId"  class="formElements"></select></td>
    <td ><input type="text" name="effort" value="(#=getMillisInHoursMinutes(obj.assig.effort)#)" size="5" class="formElements"></td>
    <td align="center"><span class="teamworkIcon delAssig del" style="cursor: pointer">d</span></td>
  </tr>
  --></div>


    <div class="__template__" type="RESOURCE_EDITOR"><!--
  <div class="resourceEditor" style="padding: 5px;">

    <h2>Project team</h2>
    <table  cellspacing="1" cellpadding="0" width="100%" id="resourcesTable">
      <tr>
        <th style="width:100px;">name</th>
        <th style="width:30px;" id="addResource"><span class="teamworkIcon" style="cursor: pointer">+</span></th>
      </tr>
    </table>

    <div style="text-align: right; padding-top: 20px"><button id="resSaveButton" class="button big">Save</button></div>
  </div>
  --></div>


    <div class="__template__" type="RESOURCE_ROW"><!--
  <tr resId="(#=obj.id#)" class="resRow" >
    <td ><input type="text" name="name" value="(#=obj.name#)" style="width:100%;" class="formElements"></td>
    <td align="center"><span class="teamworkIcon delRes del" style="cursor: pointer">d</span></td>
  </tr>
  --></div>


</div>
