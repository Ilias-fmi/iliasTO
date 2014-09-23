<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */
require_once("./Services/Object/classes/class.ilObjectListGUI.php");
/**
 * Class ilObjOrgUnitListGUI
 *
 *
 * @author: Oskar Truffer <ot@studer-raimann.ch>
 * @author: Martin Studer <ms@studer-raimann.ch>
 *
 */
class ilObjOrgUnitListGUI extends ilObjectListGUI {

    /**
     * @var ilTemplate
     */
    protected $tpl;

	function __construct(){
		global $tpl;
        $this->ilObjectListGUI();
        $this->tpl = $tpl;
		//$this->enableComments(false, false);
	}

    /**
     * initialisation
     */
    function init()
    {
        $this->static_link_enabled = true;
        $this->delete_enabled = true;
        $this->cut_enabled = true;
        $this->info_screen_enabled = true;
        $this->copy_enabled = false;
        $this->subscribe_enabled = false;
        $this->link_enabled = false;
        $this->payment_enabled = false;

        $this->type = "orgu";
        $this->gui_class_name = "ilobjorgunitgui";

        // general commands array
        include_once('./Modules/OrgUnit/classes/class.ilObjOrgUnitAccess.php');
        $this->commands = ilObjOrgUnitAccess::_getCommands();
    }



	/**
	 * no timing commands needed in orgunits.
	 */
	public function insertTimingsCommand(){
		return;
	}

	/**
	 * no social commands needed in orgunits.
	 */
	public function insertCommonSocialCommands(){
		return;
	}

    /**
     * insert info screen command
     */
    function insertInfoScreenCommand()
    {

        if ($this->std_cmd_only)
        {
            return;
        }
        $cmd_link = $this->ctrl->getLinkTargetByClass("ilinfoscreengui", "showSummary");
        $cmd_frame = $this->getCommandFrame("infoScreen");

        $this->insertCommand($cmd_link, $this->lng->txt("info_short"), $cmd_frame,
            ilUtil::getImagePath("cmd_info_s.png"));
    }

    function getCommandLink($a_cmd)
    {
        $this->ctrl->setParameterByClass("ilobjorgunitgui", "ref_id",  $this->ref_id);
        return $this->ctrl->getLinkTargetByClass("ilobjorgunitgui", $a_cmd);
    }

    /**
     * Use Icon from type
     */
    function insertIconsAndCheckboxes() {
        global $lng, $ilias;
        if (!$ilias->getSetting('custom_icons') || $this->getCheckboxStatus()) {
            parent::insertIconsAndCheckboxes();
            return;
        }
        $icons_cache = ilObjOrgUnit::getIconsCache();
        if (isset($icons_cache[$this->obj_id])) {
            $icon_file = $icons_cache[$this->obj_id];
            // icon link
            if (!$this->default_command || (!$this->getCommandsStatus() && !$this->restrict_to_goto))
            {
            }
            else
            {
                $this->tpl->setCurrentBlock("icon_link_s");

                if ($this->default_command["frame"] != "")
                {
                    $this->tpl->setVariable("ICON_TAR", "target='".$this->default_command["frame"]."'");
                }

                $this->tpl->setVariable("ICON_HREF",
                    $this->default_command["link"]);
                $this->tpl->parseCurrentBlock();
                $this->tpl->touchBlock("icon_link_e");
            }
            $this->enableIcon(false);
            parent::insertIconsAndCheckboxes();
            $this->tpl->setCurrentBlock("icon");
            $this->tpl->setVariable("ALT_ICON", $lng->txt("icon")." ".$lng->txt("obj_".$this->getIconImageType()));
            $this->tpl->setVariable("SRC_ICON", $icon_file);
            $this->tpl->parseCurrentBlock();
            $this->enableIcon(true);
            $this->tpl->touchBlock("d_1");	// indent main div
        } else {
            parent::insertIconsAndCheckboxes();
        }
  }

}
?>