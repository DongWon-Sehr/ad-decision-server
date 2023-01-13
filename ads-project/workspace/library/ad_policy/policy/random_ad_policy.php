<?php

require_once 'library/ad_policy/policy/ad_policy.php';

class RandomAdPolicy extends AdPolicy
{
    public function __construct(int $i_user_id, array $i_ads_list)
    {
        parent::__construct($i_user_id, $i_ads_list);
        $this->policy_title = "radnom";
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_target_ads(): array
    {
        $shuffled = $this->ads_list;
        shuffle($shuffled);
        $shuffled = array_slice($shuffled, 0, 3, true);

        return $shuffled;
    }
}

?>