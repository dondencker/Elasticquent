<?php  namespace Dencker\Elasticquent; 

use Illuminate\Support\ServiceProvider;

class ElasticquentServiceProvider extends ServiceProvider{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([__DIR__."/../config/elasticquent.php"=>config_path('elasticquent.php')]);
    }
}