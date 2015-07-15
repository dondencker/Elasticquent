<?php namespace Dencker\Elasticquent;

use Elasticsearch\Client as ElasticSearchClient;
use Illuminate\Database\Eloquent\Model;

class ElasticquentBuilder
{
    private $model;
    protected $query = [];
    private $wheres = [];
    private $searchTerms = [];

    function __construct(Model $model)
    {
//        if ( !( new \ReflectionClass( $model ) )-> ) )
//        {
//            throw new \InvalidArgumentException( sprintf( 'Model must implement %s', ElasticquentTrait::class ) );
//        }
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     */
    public function where($field, $value)
    {
        $operand = "=";

        /*
         * If the method receives three arguments, we assume that
         * we specify an operand as the second argument
         *
         */

        if ( count( func_get_args() ) == 3 )
        {
            $operand = $value;
            $value = func_get_arg(2);
        }

        $this->wheres[$field][$operand] = $value;

        return $this;
    }

    public function search($term = "")
    {
        $this->searchTerms = [$term];

        return $this->get();
    }

    private function resetQuery()
    {
        $this->query = [
            "_"
        ];
    }

    /**
     * @return ElasticquentResultCollection
     */
    private function get()
    {
        $result = $this->getClient()->search( $this->constructParams() );

        return new ElasticquentResultCollection($result, $this->model->newInstance());
    }

    /**
     * @return ElasticSearchClient
     */
    private function getClient()
    {
        return new ElasticSearchClient();
    }

    private function constructParams()
    {
        $params = $this->getDefaultParams();

        $search = [];

        foreach ($this->getSearchTerms() as $field => $term)
        {
            if ( is_numeric( $field ) )
            {
                $field = "_all";
            }

            $search[$field] = $term;
        }

        if ( $this->hasWheres() )
        {
            foreach ($this->getWheres() as $field => $where)
            {
                foreach($where as $operand=>$value)
                {
                    $this->parseWhere($params, $field, $operand, $value);
                }
            }

            $params['body']['query']['filtered']['query']['match'] = $search;
            return $params;
        }

        $params['body']['query']['match'] = $search;
        return $params;

    }

    /**
     * @return array
     */
    private function getDefaultParams()
    {
        return [
            'index' => $this->model->getIndexName(),
            'type'  => $this->model->getTable(),
        ];
    }

    private function hasWheres()
    {
        return !empty($this->wheres);
    }

    private function getWheres()
    {
        return $this->wheres;
    }

    private function getSearchTerms()
    {
        return $this->searchTerms;
    }

    private function parseWhere(&$params, $field, $operand, $value)
    {
        switch ($operand)
        {
            case "=":
                $params['body']['query']['filtered']['filter']['term'][$field] = $value;
                return;

            case ">":
                $params['body']['query']['filtered']['filter']['range'][$field]["gt"] = $value;
                return;

            case ">=":
                $params['body']['query']['filtered']['filter']['range'][$field]["gte"] = $value;
                return;

            case "<":
                $params['body']['query']['filtered']['filter']['range'][$field]["lt"] = $value;
                return;

            case "<=":
                $params['body']['query']['filtered']['filter']['range'][$field]["lte"] = $value;
                return;

            default:
                throw new \Exception(sprintf('Operand "%s" not known', $operand));

        }
    }
}