<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 satisfaction plugin for GLPI
 Copyright (C) 2016-2022 by the satisfaction Development Team.

 https://github.com/pluginsglpi/satisfaction
 -------------------------------------------------------------------------

 LICENSE

 This file is part of satisfaction.

 satisfaction is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 satisfaction is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with satisfaction. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginSatisfactionSurveyQuestion
 */
class PluginSatisfactionSurveyQuestion extends CommonDBChild {

   static $rightname = "plugin_satisfaction";
   public $dohistory = true;

   // From CommonDBChild
   public static $itemtype = 'PluginSatisfactionSurvey';
   public static $items_id = 'plugin_satisfaction_surveys_id';

   const YESNO      = 'yesno';
   const TEXTAREA   = 'textarea';
   const NOTE       = 'note';
   const EMOJIESCALE = 'emojiescale'; // Novo tipo de questão

   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Question', 'Questions', $nb, 'satisfaction');
   }

   /**
    * Get Tab Name used for itemtype
    *
    * NB : Only called for existing object
    *      Must check right on what will be displayed + template
    *
    * @since version 0.83
    *
    * @param CommonDBTM|CommonGLPI $item CommonDBTM object for which the tab need to be displayed
    * @param bool|int              $withtemplate boolean  is a template object ? (default 0)
    *
    * @return string tab name
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      // can exists for template
      if ($item->getType() == 'PluginSatisfactionSurvey') {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $dbu = new DbUtils();
            $table = $dbu->getTableForItemType(__CLASS__);
            return self::createTabEntry(self::getTypeName(),
                                        $dbu->countElementsInTable($table,
                                                                   [self::$items_id => $item->getID()]));
         }
         return self::getTypeName();
      }
      return '';
   }

   /**
    * show Tab content
    *
    * @since version 0.83
    *
    * @param          $item                  CommonGLPI object for which the tab need to be displayed
    * @param          $tabnum       integer  tab number (default 1)
    * @param bool|int $withtemplate boolean  is a template object ? (default 0)
    *
    * @return true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'PluginSatisfactionSurvey') {
         self::showForSurvey($item, $withtemplate);
      }
      return true;
   }

   /**
    * Question display
    *
    * @param \PluginSatisfactionSurvey $survey
    * @param string                    $withtemplate
    *
    * @return bool
    */
   public static function showForSurvey(PluginSatisfactionSurvey $survey, $withtemplate = '') {
      global $CFG_GLPI;

      $squestions_obj = new self();
      $sID            = $survey->fields['id'];
      $rand_survey    = mt_rand();

      $canadd   = Session::haveRight(self::$rightname, CREATE);
      $canedit  = Session::haveRight(self::$rightname, UPDATE);
      $canpurge = Session::haveRight(self::$rightname, PURGE);

      //check if answer exists to forbid edition
      $answer       = new PluginSatisfactionSurveyAnswer;
      $found_answer = $answer->find([self::$items_id => $survey->fields['id']]);
      if (count($found_answer) > 0) {
         echo "<span style='font-weight:bold; color:red'>" . __('You cannot edit the questions when answers exists for this survey. Disable this survey and create a new one !', 'satisfaction') . "</span>";
         $canedit  = false;
         $canadd   = false;
         $canpurge = false;
      }

      echo "<div id='viewquestion" . $sID . "$rand_survey'></div>\n";
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddQuestion$sID$rand_survey() {\n";
         $params = ['type'          => __CLASS__,
                         'parenttype'    => 'PluginSatisfactionSurvey',
                         self::$items_id => $sID,
                         'id'            => -1];
         Ajax::updateItemJsCode("viewquestion$sID$rand_survey",
                                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center'>" .
              "<a href='javascript:viewAddQuestion$sID$rand_survey();'>";
         echo __('Add a question', 'satisfaction') . "</a></div><br>\n";

      }

      // Display existing questions
      $questions = $squestions_obj->find([self::$items_id => $sID], 'id');
      if (count($questions) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __('No questions for this survey', 'satisfaction') . "</th>";
         echo "</tr></table>";
      } else {

         $rand = mt_rand();
         if ($canpurge) {
            //TODO : Detect delete to update history
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['item' => __CLASS__, 'container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr>";
         if ($canpurge) {
            echo "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand) . "</th>";
         }
         echo "<th>" . self::getTypeName(2) . "</th>";
         echo "<th>" . __('Type') . "</th></tr>";

         foreach ($questions as $question) {
            if ($squestions_obj->getFromDB($question['id'])) {
               $squestions_obj->showOne($canedit, $canpurge, $rand_survey);
            }
         }
         echo "</table>";

         if ($canpurge) {
            $paramsma['ontop'] = false;
            Html::showMassiveActions($paramsma);
            Html::closeForm();
         }
      }
   }

   /**
    * @param       $ID
    * @param array $options
    *
    * @return bool
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $survey = $options['parent'];
      }

      $surveyquestion = new self();
      if ($ID <= 0) {
         $surveyquestion->getEmpty();
      } else {
         $surveyquestion->getFromDB($ID);
      }

      if (!$surveyquestion->canView()) {
         return false;
      }

      echo "<form name='form' method='post' action='" . Toolbox::getItemTypeFormURL(self::getType()) . "'>";

      echo "<div align='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='4'>" . __('Add a question', 'satisfaction') . "</th></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . self::getTypeName(1) . "&nbsp;:</td>";
      echo "<td>";
      echo Html::textarea([
                             'name'    => 'name',
                             'value'    => $surveyquestion->fields["name"],
                             'cols'    => '50',
                             'rows'    => '4',
                             'display' => false,
                          ]);
      echo "</td>";
      echo Html::hidden(self::$items_id, ['value' =>$surveyquestion->fields[self::$items_id]]);
      echo "</td>";
      echo "<td rowspan='2'>" . __('Comments') . "</td>";
      echo "<td rowspan='2'>";
      echo Html::textarea([
                             'name'    => 'comment',
                             'value'    => $surveyquestion->fields["comment"],
                             'cols'    => '60',
                             'rows'    => '6',
                             'display' => false,
                          ]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Type') . "</td>";
      echo "<td>";
      $array = self::getQuestionTypeList();
      Dropdown::showFromArray('type', $array, ['value'     => $surveyquestion->fields['type'],
                                               'on_change' => "plugin_satisfaction_loadtype(this.value, \"" . self::NOTE . "\", \"" . self::EMOJIESCALE . "\");"]);
      echo "</td>";
      echo "</tr>";

      // Configuração para o tipo "Nota"
      $style_note = ($surveyquestion->fields['type'] == self::NOTE) ? "" : "style='display: none '";
      echo "<tr class='tab_bg_1' id='show_note' $style_note>";
      echo "<td>";
      echo __('Note on', 'satisfaction');
      echo "</td>";
      echo "<td>";
      Dropdown::showNumber('number', ['max'   => 10,
                                      'min'   => 2,
                                      'value' => $surveyquestion->fields['number'],
                                      'on_change' => "plugin_satisfaction_load_defaultvalue(\"" . Plugin::getWebDir('satisfaction') . "\", this.value);"]);
      echo "</td>";

      if (!empty($surveyquestion->fields['number'])) {
         $max_default_value = $surveyquestion->fields['number'];
      } else {
         $max_default_value = 2;
      }

      echo "<td>";
      echo __('Default value');
      echo "</td>";
      echo "<td id='default_value'>";
      Dropdown::showNumber('default_value', ['max'   => $max_default_value,
                                            'min'   => 1,
                                            'value' => $surveyquestion->fields['default_value']]);
      echo "</td>";
      echo "</tr>";

      // Configuração para o tipo "Emoji Scale"
      $style_emoji = ($surveyquestion->fields['type'] == self::EMOJIESCALE) ? "" : "style='display: none '";
      echo "<tr class='tab_bg_1' id='show_emojiescale' $style_emoji>";
      echo "<td>";
      echo __('Default value');
      echo "</td>";
      echo "<td colspan='3'>";
      Dropdown::showNumber('default_value', ['max'   => 5,
                                            'min'   => 1,
                                            'value' => $surveyquestion->fields['default_value']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='tab_bg_2 center' colspan='4'>";
      if ($ID <= 0) {
         echo Html::hidden(self::$items_id, ['value' => $survey->getField('id')]);
         echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
      } else {
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
      }
      echo "</td>";
      echo "</tr>";
      echo "</table>";

      Html::closeForm();
   }

   /**
    * Display line with name & type
    *
    * @param $canedit
    * @param $canpurge
    * @param $rand
    */
   function showOne($canedit, $canpurge, $rand) {
      global $CFG_GLPI;

      $style = '';
      if ($canedit) {
         $style = "style='cursor:pointer' onClick=\"viewEditQuestion" .
                  $this->fields[self::$items_id] .
                  $this->fields['id'] . "$rand();\"" .
                  " id='viewquestion" . $this->fields[self::$items_id] . $this->fields["id"] . "$rand'";
      }
      echo "<tr class='tab_bg_2' $style>";

      if ($canpurge) {
         echo "<td width='10'>";
         Html::showMassiveActionCheckBox(__CLASS__, $this->fields["id"]);
         echo "</td>";
      }

      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditQuestion" . $this->fields[self::$items_id] . $this->fields["id"] . "$rand() {\n";
         $params = ['type'          => __CLASS__,
                    'parenttype'    => self::$itemtype,
                    self::$items_id => $this->fields[self::$items_id],
                    'id'            => $this->fields["id"]];
         Ajax::updateItemJsCode("viewquestion" . $this->fields[self::$items_id] . "$rand",
                                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
      }

      $name = $this->fields["name"];

      echo "<td class='left'>" . nl2br($name) . "</td>";
      echo "<td class='left'>" . self::getQuestionType($this->fields["type"]) . "</td>";
      echo "</tr>";
   }

   /**
    * List of question types
    *
    * @return array
    */
   static function getQuestionTypeList() {
      $array                   = [];
      $array[self::YESNO]      = __('Yes') . '/' . __('No');
      $array[self::TEXTAREA]   = __('Text', 'satisfaction');
      $array[self::NOTE]       = __('Note', 'satisfaction');
      $array[self::EMOJIESCALE] = __('Emoji Scale', 'satisfaction'); // Novo tipo
      return $array;
   }

   /**
    * Return the type
    *
    * @return array
    */
   static function getQuestionType($type) {
      switch ($type) {
         case self::YESNO :
            return __('Yes') . '/' . __('No');
         case self::TEXTAREA :
            return __('Text', 'satisfaction');
         case self::NOTE :
            return __('Note', 'satisfaction');
         case self::EMOJIESCALE :
            return __('Emoji Scale', 'satisfaction'); // Novo tipo
      }
      return "";
   }

   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * @return an array of massive actions
    **/
   public function getForbiddenStandardMassiveAction() {

      $forbidden = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

}