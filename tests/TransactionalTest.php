<?php

declare(strict_types=1);

namespace Guiqibusixin\HyperfTest;

use Guiqibusixin\Hyperf\Database\Annotation\Transactional;
use Guiqibusixin\Hyperf\Database\Aspect\TransactionalAspect;
use Guiqibusixin\HyperfTest\Cases\AbstractTestCase;
use Guiqibusixin\HyperfTest\Stubs\ModelStub;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Model\Register;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Aop\AnnotationMetadata;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Mockery;

/**
 * @internal
 * @coversNothing
 */
class TransactionalTest extends AbstractTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        AnnotationCollector::clear();
    }

    public function testTransactionalAnnotation()
    {
        $connectionName = 'test';
        $attempts = 3;
        $model = new ModelStub();

        $connectionResolver = Mockery::mock(ConnectionResolverInterface::class);
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('transaction')->with(Mockery::any(), $attempts)->times(4);
        $connectionResolver->shouldReceive('connection')->with($connectionName)->andReturn($connection)->times(1);
        $connectionResolver->shouldReceive('connection')->with(null)->andReturn($connection)->once();
        $connectionResolver->shouldReceive('connection')->with($model->getConnectionName())->andReturn($connection)->times(2);
        Register::setConnectionResolver($connectionResolver);

        $transactional = new Transactional(connection: '', attempts: $attempts);
        $transactionalAspect = new TransactionalAspect();
        $point = Mockery::mock(ProceedingJoinPoint::class);
        $point->className = 'className';
        $point->methodName = 'methodName';
        $point->shouldReceive('getAnnotationMetadata')->andReturn(
            new AnnotationMetadata(
                [],
                [Transactional::class => $transactional]
            ),
        );

        // ?????????,????????????
        $transactionalAspect->process($point);

        // ???????????????
        $transactional->connection = $connectionName;
        $transactional->modelClass = '';
        $transactionalAspect->process($point);

        // ??????????????????
        $transactional->connection = '';
        $transactional->modelClass = $model::class;
        $transactionalAspect->process($point);

        // ???????????????????????????
        $transactional->connection = '';
        $transactional->modelClass = '';
        $point->className = $model::class;
        $transactionalAspect->process($point);

        $this->assertTrue(true);
    }
}
