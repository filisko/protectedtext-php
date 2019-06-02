<?php

namespace Test;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Filisko\ProtectedText\ApiClient;
use Filisko\ProtectedText\Site;
use Filisko\ProtectedText\Exceptions\DecryptionFailed;
use Filisko\ProtectedText\Exceptions\DecryptionNeeded;

class ApiClientTest extends TestCase
{
    protected $apiClient;

    protected $mockHandler;

    protected function setUp()
    {
        $this->mockHandler = new MockHandler();

        $httpClient = new Client([
            'handler' => $this->mockHandler,
        ]);

        $this->apiClient = new ApiClient($httpClient);
        
    }


    /**
     * @test
     */
    public function testGetNameIsCorrect()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertEquals('phptest', $this->site->getName());
    }

    /**
     * @test
     */
    public function testGetEncryptedContent()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertEquals('U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==', $this->site->getEncryptedContent());
    }

    /**
     * @test
     */
    public function testExists()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertTrue($this->site->exists());
    }

    /**
     * @test
     */
    public function testCorrectDecryption()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->assertEquals(Site::class, get_class($this->site->decrypt($password)));
    }

    /**
     * @test
     */
    public function testWrongDecryption()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = 'wrongpassword';

        $this->expectException(DecryptionFailed::class);
        $this->site->decrypt($password);
    }


    /**
     * @test
     */
    public function testGetInitHashContentBeforeDecryption()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);
        $this->site->getInitHashContent();
    }

    /**
     * @test
     */
    public function testGetInitHashContentAfterDecryption()
    {
        $json = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->site->decrypt($password);

        $this->assertEquals('cc9a5efa47bf35232088488645cf318770b5d808782fbbe069dabc0195484b716d8a4dd21ac65b7f0b7c67a34db16c3f89708709cd2b48b582a9c2296b13185b2', $this->site->getInitHashContent());
    }
}