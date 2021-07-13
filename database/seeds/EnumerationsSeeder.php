<?php

use bfinlay\SpreadsheetSeeder\SpreadsheetSeeder;

class EnumerationsSeeder extends SpreadsheetSeeder
{
    public function run()
    {
        $this->file = '/database/seeds/enumerations.csv';

        $this->inputEncodings = ['UTF-8'];
        $this->outputEncoding = 'UTF-8';

        parent::run();
    }
}
