<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPHtmlParser\Dom;

class parse extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:parse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'parse ncpa.ru to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $universities = $this->parse();
        $this->fillTables($universities);
    }

    private function parse(): array
    {
        require __DIR__ . '/../../../vendor/autoload.php';

        $universities = [];
        $dom = new Dom();

        $dom->loadFromUrl('https://ncpa.ru/index.php?option=com_content&view=article&id=174&Itemid=367&lang=ru');
        $allUniversities = $dom->find('#accordion');
        $nameUniversities = $allUniversities->find('h3');

        for ($i = 0; $i < count($nameUniversities); $i++ ) {
            preg_match("/<a.*>(.*)<\/a>/", $nameUniversities[$i]->find('a'), $matches);
            $universities[$i]['name'] = $matches[1];
        }

        $infoAboutUniversities = $allUniversities->find('div');

        for ($i = 0; $i < count($infoAboutUniversities); $i++) {
            $numOfProgram = 0;
            $allProgramsInUniversity = $infoAboutUniversities[$i]->find('table');

            preg_match("/.*href=\"(.*)\" target/", $infoAboutUniversities[$i]->find('.a'), $matches);
            $universities[$i]['website'] = '';
            if (array_key_exists(1, $matches)) {
                $universities[$i]['website'] = $matches[1];
            }

            for ($j = 0; $j < count($allProgramsInUniversity); $j++) {
                $program = $allProgramsInUniversity[$j]->find('tr');
                for ($k = 0; $k < count($program); $k++) {
                    $programInfo = $program[$k]->find('td');
                    if (count($programInfo) == 5) {
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[1], $matches);
                        $universities[$i]['programs'][$numOfProgram]['codeOfProgram'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[2], $matches);
                        $universities[$i]['programs'][$numOfProgram]['nameOfProgram'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[3], $matches);
                        $universities[$i]['programs'][$numOfProgram]['dateOfAccreditation'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[4], $matches);
                        $universities[$i]['programs'][$numOfProgram]['documentNumber'] = $matches[1];
                    }
                    if (count($programInfo) == 4) {
                        $img = $programInfo[0]->find('img');
                        if (count($img) == 1) {
                            $universities[$i]['programs'][$numOfProgram]['codeOfProgram'] = '';
                        }
                        else {
                            preg_match('/<span.*>(.*)<\/span>/', $programInfo[0], $matches);
                            if (array_key_exists(1, $matches)) {
                                $universities[$i]['programs'][$numOfProgram]['codeOfProgram'] = $matches[1];
                            }
                            else {
                                $universities[$i]['programs'][$numOfProgram]['codeOfProgram'] = '';
                            }
                        }
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[1], $matches);
                        $universities[$i]['programs'][$numOfProgram]['nameOfProgram'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[2], $matches);
                        $universities[$i]['programs'][$numOfProgram]['dateOfAccreditation'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[3], $matches);
                        $universities[$i]['programs'][$numOfProgram]['documentNumber'] = $matches[1];
                    }
                    if (count($programInfo) == 3) {
                        $universities[$i]['programs'][$numOfProgram]['codeOfProgram'] = '';
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[0], $matches);
                        $universities[$i]['programs'][$numOfProgram]['nameOfProgram'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[1], $matches);
                        $universities[$i]['programs'][$numOfProgram]['dateOfAccreditation'] = $matches[1];
                        preg_match('/<span.*>(.*)<\/span>/', $programInfo[2], $matches);
                        $universities[$i]['programs'][$numOfProgram]['documentNumber'] = $matches[1];
                    }
                    $numOfProgram += 1;
                }
            }
        }

        return $universities;
    }

    private function fillTables(array $universities): void
    {
        for ($i = 0; $i < count($universities); $i++) {
            $universityId = DB::table('universities')->insertGetId([
                'name' => $universities[$i]['name'],
                'website' => $universities[$i]['website'],
            ]);

            for($j = 0; $j < count($universities[$i]['programs']); $j++) {
                DB::insert(
                    'insert into programs (code, name, date_accreditation, document_number, university_id) ' .
                    'values (:code, :name, :date, :document, :university)', [
                    'code' => $universities[$i]['programs'][$j]['codeOfProgram'],
                    'name' => $universities[$i]['programs'][$j]['nameOfProgram'],
                    'date' => $universities[$i]['programs'][$j]['dateOfAccreditation'],
                    'document' => $universities[$i]['programs'][$j]['documentNumber'],
                    'university' => $universityId
                ]);
            }
        }
    }
}
