<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\SearchWordRequest;
use App\Http\Resources\SearchWordResource;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SearchWordController extends Controller
{
    private const DEPARTMENT_FILTER = "search-alias";
    private const MKT_FILTER = "mkt";
    private const SEARCH_TERM = "q";
    private const AMAZON_CLIENT = 'amazon-search-ui';
    private const CLIENT = 'client';
    private const DEPARTMENT = 'aps';

    /** 
    * @var Client 
    */
    protected $client;

    /**
     * SearchWordController constructor.
     * @param SearchWordService $searchWordService
     * @param Client $client
     */

    public function __construct(Client $client) {
        $this->client = $client;
    }
    /** 
    * @param SearchWordRequest $request
    * @return JsonResponse
    * @throws GuzzleException
    */
    public function autoComplete(SearchWordRequest $request){
        $keyword = $request->input('keyword');
        $amazonUrl = env('AMAZON_COMPLETE_URL');
        $url = $amazonUrl. '?' .http_build_query(
            [
                self::DEPARTMENT_FILTER => self::DEPARTMENT,
                self::CLIENT => self::AMAZON_CLIENT,
                self::MKT_FILTER => 1,
                self::SEARCH_TERM => $keyword
            ]);
            try{
                $response = $this->client->request('GET', $url, [
                    'headers'        => ['Accept' => 'application/json'],
                    'decode_content' => false
                    ]);
            }catch(GuzzleException $e){
                throw new GuzzleException($e->getMessage(),'error fetching data');
            }
            $result = json_decode($response->getBody()->getContents());
            return $result[1];
    }
}
