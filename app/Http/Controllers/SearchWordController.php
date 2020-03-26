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
                self::SEARCH_TERM => $request->input('keyword')
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
        $this->keyWord = $request->input('keyword');
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
        $strLength = strlen($this->keyWord);
        $responses = $this->autoComplete($request);
        while($strLength > 1){
            foreach($responses as $response){
                if($originalString === $response){
                    $this->score += (100 / strlen($originalString));
                }
            }
            $string = substr($this->keyWord, 0, -1);
            $this->keyWord = $string;
            $request = new SearchWordRequest();
            $request->replace(['keyword' => $this->keyWord]);
            $responses = $this->autoComplete($request);
            $strLength = strlen($this->keyWord);
            
        }
        return new SearchWordResource((object)['keyWord'=>$originalString ,'score'=> $this->score]);
    }
}
