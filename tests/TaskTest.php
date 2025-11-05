<?php

namespace App\Tests;

use App\Factory\ApiTokenFactory;
use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Zenstruck\Foundry\Test\ResetDatabase;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class TaskTest extends WebTestCase{

    use ResetDatabase;

    public function testTokenAccess(): void
    {
        list($user1, $user2, $task1, $task2) = $this->getData();
        $token1_1 = ApiTokenFactory::createOne(
            [
                'owner' => $user1,
                'expiredAt' => null
            ]
        );
        $token1_2 = ApiTokenFactory::createOne(
            [
                'owner' => $user1,
                'expiredAt' => new \DateTime('tomorrow')
            ]
        );

        $token2 = ApiTokenFactory::createOne(
            [
                'owner' => $user2,
                'expiredAt' => new \DateTime('yesterday')
            ]
        );
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1_1->getToken(),
                'HTTP_ACCEPT'        => 'application/json',
            ]
        );
        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1_2->getToken(),
                'HTTP_ACCEPT'        => 'application/json',
            ]
        );
        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token2->getToken(),
                'HTTP_ACCEPT'        => 'application/json',
            ]
        );
        $this->assertResponseStatusCodeSame(401);

    }

    private function getData(): array
    {
        $user1 = UserFactory::createOne(
            attributes:[
                'email' => 'user1@taskflow.com',
                'plainPassword' => 'user1123',
                'roles' => [],
                'name' => 'user1',
            ]
        );

        $task1 = TaskFactory::createOne(
            ['owner' => $user1, 'status' => 'todo']
        );

        $user2 = UserFactory::createOne(
            [
                'email' => 'user2@taskflow.com',
                'plainPassword' => 'user2123',
                'name' => '2',
            ]
        );

        $task2 = TaskFactory::createOne(
            ['owner' => $user2]
        );

        return [$user1, $user2, $task1, $task2];
    }

    public function testAccess(): void
    {
        list($user1, $user2, $task1, $task2) = $this->getData();

        $client = static::createClient();

        $client->request('GET', '/api/tasks');
        $this->assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/tasks/'. $task2->getId());
        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),
            content: json_encode([
                'id' => 333,
                'title' => 'task 1 updated',
                'status' => 'done',
            ])
        );
        $this->assertResponseStatusCodeSame(401);

        $client->request('POST', '/api/tasks');
        $this->assertResponseStatusCodeSame(401);

        $user1 = UserFactory::repository()->findOneByEmail($user1->getEmail());
        $client->loginUser($user1);

        $client->request('GET', '/api/tasks');
        $this->assertResponseStatusCodeSame(200);

        $client->request('GET', '/api/tasks/'. $task2->getId());
        $this->assertResponseStatusCodeSame(200);
        $response = $client->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $content);

        $client->request('GET', '/api/tasks/'. $task1->getId());
        $this->assertResponseStatusCodeSame(200);

        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                '_title' => 'test@example.com',
                'status' => 'todo1'
            ])
        );
      //  $response = $client->getResponse();
       // dd($response->getStatusCode(),  $response->getContent());

       // $response = json_decode($response->getContent(), true);

        $this->assertResponseStatusCodeSame(400);


        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'title' => 'test@example.com',
                'status' => 'todo'
            ])
        );
        $this->assertResponseStatusCodeSame(201);
        $response = $client->getResponse();
        $content = json_decode($response->getContent());

        $id = $content?->data?->id;
        $this->assertIsInt($id);

        $task = TaskFactory::repository()->find($id);

        $this->assertNotNull($task);

        $this->assertSame($task->getTitle(), 'test@example.com');
        $this->assertSame($task->getStatus(), 'todo');

        $client->request(
            'POST',
            '/api/tasks',
            content: json_encode([
                'title' => 'task 22',
                'status' => 'ok',

            ])
        );
        $this->assertResponseStatusCodeSame(400);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task2->getId()
        );
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task->getId()
        );
        $this->assertResponseStatusCodeSame(200);

        $task = TaskFactory::repository()->find($id);
        $this->assertNull($task);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),
            content: json_encode([
                'id' => 333,
                'title' => 'task 2 updated',
                'status' => 'done',
            ])
        );
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task1->getId(),
            content: json_encode([
                'id' => 333,
                'title' => 'task 1 updated',
                'status' => 'done',
            ])
        );
        $this->assertResponseStatusCodeSame(200);

        $response = $client->getResponse();
        $content = json_decode($response->getContent());
    }



}
