<?php

namespace AppBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
  public function testMissingEmailCreateIndex()
  {
      $client = static::createClient();

      try {
        $crawler = $client->request(
          'POST',
          '/user',
          [],
          [],
          ['CONTENT_TYPE' => 'application/json']
        );
      } catch(InvalidArgumentException $e) {
        $this->assertTrue(false);
      }

      $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

  public function testBadEmail1CreateIndex()
  {
      $client = static::createClient();

      try {
        $crawler = $client->request(
          'POST',
          '/user',
          ['email' => 'notanemail'],
          [],
          ['CONTENT_TYPE' => 'application/json']
        );
      } catch(InvalidArgumentException $e) {
        $this->assertTrue(false);
      }

      $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

  public function testCreateIndex()
  {
    $email = $this->generateEmail();
    $userId = $this->createUserAndReturnUserId(['email' => $email]);
  }

  public function testCreateWithAddressIndex()
  {
    $email = $this->generateEmail();
    $userId = $this->createUserAndReturnUserId([
      'email' => $email,
      'address' => 'test address'
    ]);
  }

  public function testCreateWithZipCodeIndex()
  {
    $email = $this->generateEmail();

    $userId = $this->createUserAndReturnUserId([
      'email' => $email,
      'zipCode' => '13213'
    ]);

  }

  public function testCreateWithIsActiveTrueIndex()
  {
    $email = $this->generateEmail();

    $userId = $this->createUserAndReturnUserId([
      'email' => $email,
      'isActive' => true
    ]);
  }

  public function testCreateWithIsActiveSuspendedIndex()
  {
    $email = $this->generateEmail();
    $client = static::createClient();

    $userId = $this->createUserAndReturnUserId([
      'email' => $email,
      'isActive' => false
    ]);
  }

  public function testCreateAlreadyExistsIndex()
  {
    $email = $this->generateEmail();
    $client = static::createClient();

    $this->createUserAndReturnUserId(['email' => $email]);

    $crawler = $client->request(
      'POST',
      '/user',
      ['email' => $email],
      [],
      ['CONTENT_TYPE' => 'application/json']
    );

    $this->assertEquals(400, $client->getResponse()->getStatusCode());
    $this->assertContains('user already exist.', $crawler->text());
  }

  public function testGetNotFoundIndex()
  {
    $client = static::createClient();

    $crawler = $client->request('GET', '/user/0');

    $this->assertEquals(404, $client->getResponse()->getStatusCode());
  }

  public function testGetFoundActiveIndex()
  {
    $client = static::createClient();

    $email = $this->generateEmail();
    $address = 'test ad;dress 2';
    $zipCode = '23224';
    $isActive = true;

    $userId = $this->createUserAndReturnUserId([
        'email' => $email,
        'address' => $address,
        'zipCode' => $zipCode,
        'isActive' => $isActive
      ]);

    $crawler = $client->request('GET', '/user/' . $userId);

    $this->assertEquals(200, $client->getResponse()->getStatusCode());

    $data = json_decode($client->getResponse()->getContent(), true);

    $this->assertTrue($data['userId'] > 0);
    $this->assertEquals($email, $data['email']);
    $this->assertEquals($address, $data['address']);
    $this->assertEquals($zipCode, $data['zipCode']);
    $this->assertEquals($isActive, $data['isActive']);
    $this->assertEquals('Active', $data['status']);
  }

  public function testGetFoundSuspendedIndex()
  {
    $client = static::createClient();

    $email = $this->generateEmail();
    $address = 'test address 2';
    $zipCode = '23224';
    $isActive = false;

    $userId = $this->createUserAndReturnUserId([
      'email' => $email,
      'address' => $address,
      'zipCode' => $zipCode,
      'isActive' => $isActive
    ]);


    $crawler = $client->request('GET', '/user/' . $userId);

    $this->assertEquals(200, $client->getResponse()->getStatusCode());

    $data = json_decode($client->getResponse()->getContent(), true);

    $this->assertTrue($data['userId'] > 0);
    $this->assertEquals($email, $data['email']);
    $this->assertEquals($address, $data['address']);
    $this->assertEquals($zipCode, $data['zipCode']);
    $this->assertEquals($isActive, $data['isActive']);
    $this->assertEquals('Suspended', $data['status']);
  }

  public function testUpdateAlreadyExistIndex()
  {
    $client = static::createClient();

    $email1 = $this->generateEmail();
    $email2 = $this->generateEmail();

    $userId1 = $this->createUserAndReturnUserId(['email' => $email1]);
    $userId2 = $this->createUserAndReturnUserId(['email' => $email2]);

    $crawler = $client->request('PUT', '/user/' . $userId1, [
      'email' => $email2
    ]);

    $this->assertEquals(500, $client->getResponse()->getStatusCode());
    $this->assertContains('user already exist.', $crawler->text());
  }

  public function testUpdateGoodIndex()
  {
    $client = static::createClient();

    $email1 = $this->generateEmail();
    $email2 = $this->generateEmail();

    $userId1 = $this->createUserAndReturnUserId(['email' => $email1]);

    $crawler = $client->request('PUT', '/user/' . $userId1, [
      'email' => $email2
    ]);

    $this->assertEquals(200, $client->getResponse()->getStatusCode());
    $this->assertContains('Updated User with id ' . $userId1, $crawler->text());
  }

  public function testDeleteExistIndex()
  {
    $email = $this->generateEmail();
    $client = static::createClient();

    $userId = $this->createUserAndReturnUserId(['email' => $email]);
    $crawler = $client->request('DELETE', '/user/' . $userId);

    $this->assertEquals(200, $client->getResponse()->getStatusCode());
  }

  public function testDeleteNotExistIndex()
  {
    $email = $this->generateEmail();
    $client = static::createClient();

    $userId = $this->createUserAndReturnUserId(['email' => $email]);

    $crawler = $client->request('DELETE', '/user/' . $userId);
    $this->assertEquals(200, $client->getResponse()->getStatusCode());

    $crawler = $client->request('DELETE', '/user/' . $userId);
    $this->assertEquals(404, $client->getResponse()->getStatusCode());
  }

  /**
   * Returns a randomly generate email.
   */
  private function generateEmail() {
    return 'ut_' . debug_backtrace(null, 2)[1]['function'] . '_'. rand() . '@example.com';
  }

  /**
    * Creates a User via a mock rest call with the given data and returns the
    * user id.
    *
    * This method will assert that the is userId > 0
    *
    * @return string
    */
  private function createUserAndReturnUserId($data) {
    $client = static::createClient();

    $crawler = $client->request(
      'POST',
      '/user',
      $data,
      [],
      ['CONTENT_TYPE' => 'application/json']
    );

    $this->assertEquals(201, $client->getResponse()->getStatusCode());
    $this->assertContains('Created User with id ', $crawler->text());

    $text = $crawler->text();
    $arr = explode(' ', $text);
    $userId = str_replace('.', '', $arr[count($arr) - 1]);
    $this->assertTrue($userId > 0);

    // Extract Location
    $headers = explode("\n", $client->getResponse() . '');
    $location = null;
    foreach ($headers as $row) {
      $arr = explode(': ', $row);
      if (count($arr) > 1) {
        list($key, $value) = $arr;
        if ($key == 'Location') {
          $location = $value;
          break;
        }
      }
    }
    $this->assertContains('/user/' . $userId, $location);

    return $userId;
  }
}
