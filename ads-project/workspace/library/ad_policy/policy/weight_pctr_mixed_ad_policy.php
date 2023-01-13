<?php

require_once 'library/ad_policy/policy/ad_policy.php';

class WeightPctrMixedAdPolicy extends AdPolicy
{
    private $ctr_predict_server = "https://predict-ctr-pmj4td4sjq-du.a.run.app";

    public function __construct(int $i_user_id, array $i_ads_list)
    {
        parent::__construct($i_user_id, $i_ads_list);
        $this->policy_title = "weight_pctr_mixed";
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_target_ads(): array
    {
        $_ad_campaign_ids = implode(",", array_column($this->ads_list, "id"));
        $_api_url = $this->ctr_predict_server . "/?user_id={$this->user_id}&ad_campaign_ids={$_ad_campaign_ids}";
        $_api_resp = file_get_contents($_api_url);
        $_pctr = json_decode($_api_resp, true)["pctr"];
        
        arsort($_pctr);
        $_pctr = array_slice($_pctr, 0, 3, true);
        $_target_ads_indices = array_keys($_pctr);
        $_target_index = $_target_ads_indices[0];

        $o_target_ads = [];
        $_current_ads_list = $this->ads_list;

        $o_target_ads []= $_current_ads_list[$_target_index];
        unset($_current_ads_list[$_target_index]);
        
        $_current_ads_list = $this->ads_list;
        $total_ads_count = count($_current_ads_list);
        
        for ( $i = 0; $i < $total_ads_count; $i++ ) {
            if ($i > 2) break;
            if ( count($_current_ads_list) > 0 ) {
                $_weights = array_column($_current_ads_list, "weight");
                $_index = $this->get_weighted_random_index($_weights);
                $o_target_ads []= $_current_ads_list[$_index];

                unset($_current_ads_list[$_index]);
                $_current_ads_list = array_values($_current_ads_list);
            } else {
                break;
            }
        }

        return $o_target_ads;
    }
}

?>