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
use InvalidArgumentException;

class ApiClientTest extends TestCase
{
    protected $apiClient;

    protected $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $httpClient = new Client([
            'handler' => $this->mockHandler,
        ]);

        $this->apiClient = new ApiClient($httpClient);
        
        $this->existentSiteJson = <<<JSON
{
"eContent":"U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;
        
        $this->existentSiteJsonWithMetadata = <<<JSON
{
"eContent":"U2FsdGVkX18hEjRRtWFyLwYvuGEVUQi9A93527teKIRjnviukNmKnU4y8fAeHHoN9cpmHH4KHMEA0xKC1ai4IOzQeyysJQY8tnukO9H3M8bwCpUoNDFIrxpCMHDNDbaWBs/6kcwcOVIagKlkcJeRk6GTISJJH9DsussZF2O2WGSY6KWtzH+y/eZE8qUwkM3gIHQNwQwQ7kciNhiakkama/g+5XOlXN+J5uIBzi3gW4r2Una6Pzw68zGLvxAwsitUlWtcBqeP37tIuLKJpwRnxwiWL8aJDiph+w2QCq2FTKC1hRoIEd6U5PeLQqQ57xl66Lod4ZNe2ZhVrbZyaCYtfW0LBDWjXt/d3/jdldwmzm3U3h8V9rhpm4ZhK9sUYV3XIKsKAEQPN55DFqPhz5FbokGa8//jdBSOHGZ2i2kkYhTrvm8/gReniJqq5EYzGM5I/bOiB1DuFC0J8z2R5zXMl05E8XdzhI1RrDpEDpXl+zcrXeZhzKuHdhPtyPqTFyMGIoC1xbisj35OwVJuMyTYBGo39HYFGLlftZmax9yPyIfUSo3VzTMyt4Rbgj6xfu1basPhMsWDTejjzZloC4YxZyZ0paPeZmVB5U28UAzZDxztLHsyTzMV229nkLJBpM/O+Aek507NPeGP9PQEBCLsXBI1vvzm7k4lyYmzps383iQga9M28WqMIaK5HQP0OLieScXVVUKhRQqz+rgy48aeug==",
"isNew":false,
"currentDBVersion":2,
"expectedDBVersion":2
}
JSON;
    }

    /**
     * @test
     */
    public function testGetName()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertEquals('phptest', $this->site->getName());
    }

    /**
     * @test
     */
    public function testGetEncryptedContent()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertEquals('U2FsdGVkX1/HkflrmLEteQpOURUCE9BckYfvvkh1/TwmiyAfTWjFV7bDEChbjOBPsT1ZiyexpmkrR9mlUeSDa08ZLZJ2r38VO38hDl48X7HKDAo7v+wQ2E+PLOleittB/j1k7/EuI2tAtr6yyBJXnpzb0pw5esejvM/nNFxFLoVbFDl6oWF9dLE/L5YUAUaWjhmdi7z97zQZUxymHEYE/aeofHtbWR3561qz6IaHDXvfPPAcc/rlXIo/ayUZRWHNNITnYnHdDNRr1VgGvpHA/E0nrGUe8JzwrRPpLpRv1kmswGbxh1JPjqXxzMq9MEtlQaCCyNTyzz6nzz0omkVWZWfWrrrs/20ePkM5MP2ECYtys8r+/kOm/7afRcZlA7k90F4tVT56Rk2piwnVhcNg5w==', $this->site->getEncryptedContent());
    }

    /**
     * @test
     */
    public function testGetDecryptedContentBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);
        $this->site->getDecryptedContent();
    }

    /**
     * @test
     */
    public function testGetDecryptedContentOnSiteWithoutMetadata()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $this->site->decrypt(123123);

        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond content');
    }

    /**
     * @test
     */
    public function testGetDecryptedContentOnSiteWithMetadata()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $this->site->decrypt(123123);

        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488c♻ Reload this website to hide mobile app metadata! ♻{"version":1,"title":"Title","color":-1118482}');
    }

    /**
     * @test
     */
    public function testExists()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertTrue($this->site->exists());
    }

    /**
     * @test
     */
    public function testCorrectDecryptionReturnsSiteInstance()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->assertEquals(Site::class, get_class($this->site->decrypt($password)));
    }

    /**
     * @test
     */
    public function testWrongDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = 'wrongpassword';

        $this->expectException(DecryptionFailed::class);
        $this->site->decrypt($password);
    }

    /**
     * @test
     */
    public function testGetInitHashContentBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

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
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->site->decrypt($password);

        $this->assertEquals('cc9a5efa47bf35232088488645cf318770b5d808782fbbe069dabc0195484b716d8a4dd21ac65b7f0b7c67a34db16c3f89708709cd2b48b582a9c2296b13185b2', $this->site->getInitHashContent());
    }

    /**
     * @test
     */
    public function testGetCurrentHashContentBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);
        $this->site->getCurrentHashContent();
    }

    /**
     * @test
     */
    public function testGetCurrentHashContentAfterDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->site->decrypt($password);

        $this->assertEquals('cc9a5efa47bf35232088488645cf318770b5d808782fbbe069dabc0195484b716d8a4dd21ac65b7f0b7c67a34db16c3f89708709cd2b48b582a9c2296b13185b2', $this->site->getCurrentHashContent());
    }

    /**
     * @test
     */
    public function testSetEmptyPasswordThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';

        $this->expectException(InvalidArgumentException::class);
        $this->site->setPassword('');
    }

    /**
     * @test
     */
    public function testIsDecryptedAfterDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->assertTrue($this->site->isDecrypted());
    }

    /**
     * @test
     */
    public function testIsDecryptedBeforeDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->assertFalse($this->site->isDecrypted());
    }

    /**
     * @test
     */
    public function testHasMetadataOnSiteWithoutMetadataBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        
        $this->expectException(DecryptionNeeded::class);
        $this->site->hasMetadata();
    }

    /**
     * @test
     */
    public function testHasMetadataOnSiteWithoutMetadataAfterDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->assertFalse($this->site->hasMetadata());
    }

    /**
     * @test
     */
    public function testHasMetadataOnSiteWithMetadataAfterDecryption()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->assertTrue($this->site->hasMetadata());
    }


    /**
     * @test
     */
    public function testGetMetadataOnSiteWithoutMetadataBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);
        $this->site->getMetadata();
    }

    /**
     * @test
     */
    public function testGetMetadataOnSiteWithoutMetadataAfterDecryptionReturnsEmptyArray()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->assertSame([], $this->site->getMetadata());
    }

    /**
     * @test
     */
    public function testGetMetadataOnSiteWithMetadataAfterDecryptionReturnsArray()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->assertIsArray($this->site->getMetadata());
    }

    // /**
    //  * @test
    //  */
    // public function testSetMetadataOnSiteWithoutMetadataBeforeDecryptionThrowsException()
    // {
    //     $json = $this->existentSiteJson;

    //     $this->mockHandler->append(new Response(200, [], $json));
    //     $this->site = $this->apiClient->get('phptest');

    //     $this->expectException(DecryptionNeeded::class);
    //     $this->site->getMetadata();
    // }

    /**
     * @test
     */
    public function testSetMetadataOnSiteWithMetadataBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);

        $metadata = [
            'title' => 'Some title'
        ]; 
        $this->site->setMetadata($metadata);
    }

    /**
     * @test
     */
    public function testSetMetadataOnSiteWithMetadataAfterDecryption()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $metadata = [
            'title' => 'Some title'
        ];
        $this->site->setMetadata($metadata);

        $this->assertEquals($metadata['title'], $this->site->getMetadata('title'));
    }
    
    /**
     * @test
     */
    public function testSetMetadataOnSiteWithoutMetadataAfterDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        
        $metadata = [
            'title' => 'Some title'
        ];
        $this->site->setMetadata($metadata);
        
        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488c♻ Reload this website to hide mobile app metadata! ♻{"title":"Some title","version":1,"color":-1118482}');
    }
    
    /**
     * @test
     */
    public function testSetMetadataWithEmptyArrayRemovesMetadataOnSiteWithMetadataAfterDecryption()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->site->setMetadata([]);
        
        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond content');
    }
    
    /**
     * @test
     */
    public function testRemoveMetadataBeforeDecryptionThrowsException()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');

        $this->expectException(DecryptionNeeded::class);
        $this->site->removeMetadata();
    }
    
    /**
     * @test
     */
    public function testRemoveMetadataOnSiteWithMetadataAfterDecryption()
    {
        $json = $this->existentSiteJsonWithMetadata;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->site->removeMetadata();
        
        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond content');
    }
    
    /**
     * @test
     */
    public function testRemoveMetadataOnSiteWithoutMetadataAfterDecryption()
    {
        $json = $this->existentSiteJson;

        $this->mockHandler->append(new Response(200, [], $json));
        $this->site = $this->apiClient->get('phptest');
        $password = '123123';
        $this->site->decrypt($password);

        $this->site->removeMetadata();
        
        $this->assertEquals($this->site->getDecryptedContent(), 'first contentf47c13a09bfcad9eb1f81fbf12c04516e0d900e409a74c660f933e69cf93914e16bc9facc7d379a036fe71468bd4504f2a388a0a28a9b727a38ab7843203488csecond content');
    }

    // /**
    //  * @test
    //  */
    // public function testSetMetadataOnSiteWithMetadataAfterDecryption()
    // {
    //     $json = $this->existentSiteJsonWithMetadata;

    //     $this->mockHandler->append(new Response(200, [], $json));
    //     $this->site = $this->apiClient->get('phptest');

    //     $password = '123123';
    //     $this->site->decrypt($password);

    //     // $stub = $this->createMock(Site::class);
    //     // $stub->method('getMetadata')
    //     //     ->willReturn([
    //     //         'title' => 'Some'
    //     //     ]);

    //     // $this->assertSame([
    //     //     'title' => 'Some'
    //     // ], $stub->getMetadata());

        
    //     $metadata = [
    //         'title' => 'Some'
    //     ];

    //     dd($this->site->setMetadata($metadata));

    //     $this->assertEquals($metadata['title'], $this->site->getMetadata('title'));
    // }
}