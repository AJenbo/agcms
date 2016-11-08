<?php

class SajaxTest extends TestCase
{
    public function test_handleClientRequest()
    {
        SAJAX::handleClientRequest();
    }

    public function test_export()
    {
        SAJAX::export(['validemail' => []]);
    }

    /**
     * @expectedException Exception
     */
    public function test_export_does_not_exists()
    {
        SAJAX::export(['does_not_exists' => []]);
    }

    public function test_showJavascript()
    {
        ob_start();
        SAJAX::showJavascript();
        $output = ob_get_clean();

        $expected = 'sajax_debug_mode=false;sajax_failure_redirect = "";function x_validemail() {return sajax_do_call("validemail", arguments, "GET", true, "");}';
        $this->assertEquals($expected, $output);
    }
}
