<?php

require_once 'library/ad_policy/policy/ad_policy.php';

class PctrAdPolicy extends AdPolicy
{
    private $PCTR_SERVER = "https://predict-ctr-pmj4td4sjq-du.a.run.app";

    public function __construct(int $i_user_id, array $i_ads_list)
    {
        parent::__construct($i_user_id, $i_ads_list);
        $this->policy_title = "pctr";
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_target_ads(): array
    {
        $_ad_campaign_ids = implode(",", array_column($this->ads_list, "id"));
        $_api_url = $this->PCTR_SERVER . "/?user_id={$this->user_id}&ad_campaign_ids={$_ad_campaign_ids}";
        $_api_resp = file_get_contents($_api_url);
        $_pctr = json_decode($_api_resp, true)["pctr"];
        
        arsort($_pctr);
        $_pctr = array_slice($_pctr, 0, 3, true);
        $_target_ads_indices = array_keys($_pctr);

        $o_target_ads = [];
        foreach ($_target_ads_indices as $_target_index) {
            $o_target_ads []= $this->ads_list[$_target_index];
        }
        return $o_target_ads;
    }
}

?>