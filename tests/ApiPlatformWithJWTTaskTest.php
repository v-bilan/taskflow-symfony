<?php

namespace App\Tests;

use App\Factory\TaskFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Symfony\Component\HttpFoundation\Request;

class ApiPlatformWithJWTTaskTest extends WebTestCase
{
    use ResetDatabase;

    public function testSomething(): void
    {
        list ($user1, $user2, $task1, $task2) = $this->getData();

        $client = static::createClient();

        $client->request('GET', '/api/tasks');
        $this->assertResponseStatusCodeSame(401);

        $client->request('GET', '/api/tasks/'. $task2->getId());
        $this->assertResponseStatusCodeSame(401);

        $client->jsonRequest(
            'POST',
            '/api/login_check',
            [
              'username' => $user1->getEmail(),
              'password' => 'user1123',
            ]
          );

        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('token', $data);

        $token1 = $data['token'];

        $client->request(
            'GET',
            '/api/tasks',
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertTrue(json_validate($client->getResponse()->getContent()));

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(2, $data['totalItems']);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),
            content: json_encode([
                'id' => 333,
                'title' => 'task 1 updated',
                'status' => 'done',
            ])
        );

        //dd($client->getResponse()->getStatusCode());
        $this->assertResponseStatusCodeSame(401);


        $client->request(
            'PATCH',
            '/api/tasks/' . $task2->getId(),

            content: json_encode( [
                'id' => 333,
                'title' => 'task 2 updated',
                'status' => 'done',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );
   //     dd($client->getResponse()->getStatusCode());
   //     dd($client->getResponse()->getContent());
        $this->assertResponseStatusCodeSame(403);

        $client->request(
            'PATCH',
            '/api/tasks/' . $task1->getId(),

           content: json_encode( [
                'id' => 333,
                'title' => 'task 1 updated',
                'status' => 'done',
            ]),
            server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]

        );

       // dd($client->getRequest()->__toString());
       // dd($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(200);

        $client->request(
            'GET',
            '/api/tasks/' . $task1->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ]
        );
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('task 1 updated', $data['title']);
        $this->assertSame('done', $data['status']);

        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: ['CONTENT_TYPE' => 'application/merge-patch+json'],
            content: json_encode([
                'title' => 'test@example.com',
                'status' => 'todo'
            ])
        );

        $this->assertResponseStatusCodeSame(401);

        $client->request(
            method: 'POST',
            uri: '/api/tasks',
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
            content: json_encode([
                'title' => 'test@example.com',
                'status' => 'todo'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $id = $task1->getId();
/*
        $response = $client->getResponse();

        dd($response);
        $content = json_decode($response->getContent());
dd($content);
        $id = $content?->data?->id;

        $this->assertIsInt($id);

        $task = TaskFactory::repository()->find($id);

        $this->assertNotNull($task);
*/
        $client->request(
            'DELETE',
            '/api/tasks/' . $task2->getId()
        );
        $this->assertResponseStatusCodeSame(401);

        $client->request(
            'DELETE',
            '/api/tasks/' . $task1->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
        );
        $response = $client->getResponse();

        $this->assertResponseStatusCodeSame(204);

        $task = TaskFactory::repository()->find($id);
        $this->assertNull($task);
        $client->request(
            'DELETE',
            '/api/tasks/' . $task2->getId(),
            server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token1
            ],
        );
        $this->assertResponseStatusCodeSame(403);
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
}
