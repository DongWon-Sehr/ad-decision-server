<?php

abstract class AdPolicy
{
    public $policy_title;
    protected $user_id;
    protected $ads_list;

    public function __construct(int $i_user_id, array $i_ads_list)
    {
        $this->user_id = $i_user_id;
        $this->ads_list = $i_ads_list;
        $this->policy_title = "policy_title_placeholder";
    }

    public function __destruct()
    {
    }

    public function get_policy_title() {
        return $this->policy_title;
    }

    protected function get_weighted_random_index( array $i_weights ): int
    {
        $r = rand(1, array_sum($i_weights));
        for($i=0; $i<count($i_weights); $i++) {
          $r -= $i_weights[$i];
          if($r < 1) return $i;
        }
        return 0;
    }

    abstract function get_target_ads(): array;
}
?>