<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SearchWordRequest;
use App\Http\Resources\SearchWordResource;
use GuzzleHttp\Client;
use GuzzleHttp\GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;

class SearchWordController extends Controller
{
    private const DEPARTMENT_FILTER = "search-alias";
    private const MKT_FILTER = "mkt";
    private const SEARCH_TERM = "q";
    private const AMAZON_CLIENT = 'amazon-search-ui';
    private const CLIENT = 'client';
    private const DEPARTMENT = 'aps';

    /** 
    * @var int 
    */
    public $score;

    /** 
    * @var int 
    */
    public $stringLength;

    /** 
    * @var string 
    */
    public $keyWord;

    /** 
    * @var Client 
    */
    protected $client;

    /**
     * SearchWordController constructor.
     * @param SearchWordService $searchWordService
     * @param Client $client
     */

    public function __construct(Client $client,int $score = 0,int $stringLength = 0, string $keyWord = Null) {
        $this->client = $client;
        $this->score = $score;
        $this->keyWord = $keyWord;
        $this->stringLength = $stringLength;
    }
    /** 
    * @param SearchWordRequest $request
    * @return array $responses
    * @throws ClientException
    */
    public function autoComplete(SearchWordRequest $request){
        $amazonUrl = env('AMAZON_COMPLETE_URL');
        $url = $amazonUrl. '?' .http_build_query(
            [
                self::DEPARTMENT_FILTER => self::DEPARTMENT,
                self::CLIENT => self::AMAZON_CLIENT,
                self::MKT_FILTER => 1,
                self::SEARCH_TERM => mb_strtolower($request->input('keyword'))
            ]);
            try{
                $response = $this->client->request('GET', $url, [
                    'headers' => ['Accept' => 'application/json'],
                    'decode_content' => false
                    ]);
            }catch(ClientException $e){
                throw new ClientException($e->getResponse());
            }
            $result = json_decode($response->getBody()->getContents());

            return $result[1];
    }
    /**
    *Case I single search approach
    * @param SearchWordRequest $request
    * @return SearchWordResource
    */
    public function singleSearch(SearchWordRequest $request){
        $this->keyWord = mb_strtolower($request->input('keyword'));
        $responses = $this->autoComplete($request);
        foreach($responses as $response){
            if($this->keyWord === $response){
                $this->score += 55;
            }
            else if(preg_match("/\b{$this->keyWord}\b/",$response)){
                $this->score += 5;
            }
        }
        return new SearchWordResource((object)['keyWord' => $this->keyWord,'score'=> $this->score]);
    }
    /**
    *Case II iterate approach
    * @param SearchWordRequest $request
    * @return SearchWordResource
    */
    public function iterateSearch(SearchWordRequest $request){
        $originalString = $request->input('keyword');
        $this->keyWord = $originalString;
        $this->stringLength = strlen($this->keyWord);
        $responses = $this->autoComplete($request);
        while($this->stringLength >= 1){
            foreach($responses as $response){
                if(mb_strtolower($originalString) === $response){
                    $this->score += (100 / strlen($originalString));
                }
            }
            $string = substr($this->keyWord, 0, -1);
            $this->keyWord = $string;
            $request = new SearchWordRequest();
            $request->replace(['keyword' => $this->keyWord]);
            $responses = $this->autoComplete($request);
            $this->stringLength = strlen($this->keyWord);
        }
        return new SearchWordResource((object)['keyWord'=>$originalString ,'score'=> $this->score]);
    }

    /**
    *Case III iterate approach
    * @param SearchWordRequest $request
    * @return SearchWordResource
    */
    public function iterateSearchWeight(SearchWordRequest $request){
        $originalString = $request->input('keyword');
        $originalLength = strlen($originalString);
        //The sum { $sumRatio } of all string ratio.string with length 6 has ratio 1:2:3:4:5:6 = 21
        $sumRatio =  ($originalLength * ($originalLength + 1))/ 2;
        $this->keyWord = $originalString;
        $this->stringLength = strlen($this->keyWord);
        $responses = $this->autoComplete($request);
        while($this->stringLength >= 1){
            foreach($responses as $response){
                if(mb_strtolower($originalString) === $response){
                    //smallest ratio having highest score and the highest ratio having smallest score
                    $this->score += ((($originalLength - $this->stringLength) + 1) / $sumRatio) * 100;
                }
            }
            $string = substr($this->keyWord, 0, -1);
            $this->keyWord = $string;
            $request = new SearchWordRequest();
            $request->replace(['keyword' => $this->keyWord]);
            $responses = $this->autoComplete($request);
            $this->stringLength = strlen($this->keyWord);
        }
        return new SearchWordResource((object)['keyWord'=>$originalString ,'score'=> $this->score]);
    }

    /**
    *Case IV iterate approach with first word
    * @param SearchWordRequest $request
    * @return SearchWordResource
    */
    public function iterateSearchFirstWord(SearchWordRequest $request){
        $originalStrings = $request->input('keyword');
        //convert string to array
        $originalStringArray = explode(" ",$originalStrings);
        //take the first array
        $originalString = $originalStringArray[0];
        $originalLength = strlen($originalString);
        //The sum { $sumRatio } of all string ratio.string with length 6 has ratio 1:2:3:4:5:6 = 21
        $sumRatio =  ($originalLength * ($originalLength + 1))/ 2;
        $this->keyWord = $originalString;
        $this->stringLength = strlen($this->keyWord);
        $request = new SearchWordRequest();
        $request->replace(['keyword' => $originalString]);
        $responses = $this->autoComplete($request);
        while($this->stringLength >= 1){
            foreach($responses as $response){
                if(mb_strtolower($originalStrings) === $response){
                    //smallest ratio having highest score and the highest ratio having smallest score
                    $this->score += ((($originalLength - $this->stringLength) + 1) / $sumRatio) * 100;
                }
            }
            $string = substr($this->keyWord, 0, -1);
            $this->keyWord = $string;
            $request = new SearchWordRequest();
            $request->replace(['keyword' => $this->keyWord]);
            $responses = $this->autoComplete($request);
            $this->stringLength = strlen($this->keyWord);
        }
        return new SearchWordResource((object)['keyWord'=>$originalStrings , 'score'=> $this->score]);
    }
}
