<?php

require_once 'library/ad_policy/policy/ad_policy.php';

class WeightAdPolicy extends AdPolicy
{
    public function __construct(int $i_user_id, array $i_ads_list)
    {
        parent::__construct($i_user_id, $i_ads_list);
        $this->policy_title = "weight";
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_target_ads(): array
    {
        $total_ads_count = count($this->ads_list);
        $_current_ads_list = $this->ads_list;
        $o_target_ads = [];
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