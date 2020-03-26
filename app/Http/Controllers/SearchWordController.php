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

    public function __construct(Client $client,int $score = 0, string $keyWord = Null) {
        $this->client = $client;
        $this->score = $score;
        $this->keyWord = $keyWord;
    }
    /** 
    * @param SearchWordRequest $request
    * @return SearchWordResource
    * @throws ClientException
    */
    public function autoComplete(SearchWordRequest $request){
        $this->keyWord = $request->input('keyword');
        $amazonUrl = env('AMAZON_COMPLETE_URL');
        $url = $amazonUrl. '?' .http_build_query(
            [
                self::DEPARTMENT_FILTER => self::DEPARTMENT,
                self::CLIENT => self::AMAZON_CLIENT,
                self::MKT_FILTER => 1,
                self::SEARCH_TERM => $this->keyWord
            ]);
            try{
                $response = $this->client->request('GET', $url, [
                    'headers' => ['Accept' => 'application/json'],
                    'decode_content' => false
                    ]);
            }catch(ClientException $e){
                throw new ClientException($e->getResponse(),'error fetching data');
            }
            $result = json_decode($response->getBody()->getContents());
            $this->singleSearch($result[1]);
            return new SearchWordResource((object)['keyWord' => $this->keyWord,'score'=> $this->score]);
    }
    /**
    *Case I single search approach
    * @param array $responses
    * @return void
    */
    public function singleSearch(array $responses){
        
        foreach($responses as $response){
            if($this->keyWord === $response){
                $this->score += 55;
            }
            else if(preg_match("/\b{$this->keyWord}\b/",$response)){
                $this->score += 5;
            }
        }
    }
}
