<?php

namespace Netnak\CloudBurst\Widgets;

use Statamic\Widgets\Widget;

class CloudBurstWidget extends Widget
{
  
    public function html()
    {
        return view('cloudburst::widgets.cloudburst');
    }
}