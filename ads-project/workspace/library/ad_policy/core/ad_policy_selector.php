<?php

require_once 'library/ad_policy/policy/random_ad_policy.php';
require_once 'library/ad_policy/policy/weight_ad_policy.php';
require_once 'library/ad_policy/policy/pctr_ad_policy.php';
require_once 'library/ad_policy/policy/weight_pctr_mixed_ad_policy.php';

class AdPolicySelector {
    private $ad_policy;

    function __construct(int $i_user_id, array $i_ads_list)
    {
        switch ( $i_user_id % 4 ) {
            case 0:
                $this->ad_policy = new RandomAdPolicy($i_user_id, $i_ads_list);
                break;
            case 1:
                $this->ad_policy = new WeightAdPolicy($i_user_id, $i_ads_list);
                break;
            case 2:
                $this->ad_policy = new PctrAdPolicy($i_user_id, $i_ads_list);
                break;
            case 3:
                $this->ad_policy = new WeightPctrMixedAdPolicy($i_user_id, $i_ads_list);
                break;
        }
        
    }

    function __destruct()
    {    
    }

    public function get_policy_title() {
        return $this->ad_policy->get_policy_title();
    }

    public function get_target_ads() {
        return $this->ad_policy->get_target_ads();
    }
}

?>