<?php namespace Soloevgen\Testcover;
class ClassOne
{
    public function methodAdd($a, $b){
        $c = $a;
        $a = $b;
        $b = $c;
        $c = $a;
        return $a + $b;
    }

    public function methodSub($a, $b)
    {
        return $a - $b;
    }
}