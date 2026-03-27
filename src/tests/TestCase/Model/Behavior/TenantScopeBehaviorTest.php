<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Behavior\TenantScopeBehavior;
use App\Tenant\TenantContext;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;

/**
 * Tests for TenantScopeBehavior.
 */
class TenantScopeBehaviorTest extends TestCase
{
    protected TenantScopeBehavior $behavior;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::reset();

        $this->table = $this->getMockBuilder(Table::class)
            ->onlyMethods(['getAlias'])
            ->getMock();
        $this->table->method('getAlias')->willReturn('Monitors');

        $this->behavior = new TenantScopeBehavior($this->table, []);
    }

    protected function tearDown(): void
    {
        TenantContext::reset();
        unset($this->behavior, $this->table);
        parent::tearDown();
    }

    // --- beforeFind tests ---

    public function testBeforeFindAddsConditionWhenContextIsSet(): void
    {
        TenantContext::setCurrentOrgId(7);

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->once())
            ->method('where')
            ->with(['Monitors.organization_id' => 7])
            ->willReturnSelf();

        $event = new Event('Model.beforeFind');
        $options = new ArrayObject();

        $this->behavior->beforeFind($event, $query, $options, true);
    }

    public function testBeforeFindSkipsWhenContextNotSet(): void
    {
        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->never())
            ->method('where');

        $event = new Event('Model.beforeFind');
        $options = new ArrayObject();

        $this->behavior->beforeFind($event, $query, $options, true);
    }

    public function testBeforeFindSkipsWhenSkipTenantScopeOption(): void
    {
        TenantContext::setCurrentOrgId(7);

        $query = $this->getMockBuilder(SelectQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->never())
            ->method('where');

        $event = new Event('Model.beforeFind');
        $options = new ArrayObject(['skipTenantScope' => true]);

        $this->behavior->beforeFind($event, $query, $options, true);
    }

    // --- beforeSave tests ---

    public function testBeforeSaveSetsOrgIdOnNewEntity(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity();
        $entity->setNew(true);

        $event = new Event('Model.beforeSave');
        $options = new ArrayObject();

        $this->behavior->beforeSave($event, $entity, $options);

        $this->assertSame(5, $entity->get('organization_id'));
    }

    public function testBeforeSaveDoesNotOverrideExistingOrgId(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity(['organization_id' => 5]);
        $entity->setNew(true);

        $event = new Event('Model.beforeSave');
        $options = new ArrayObject();

        $this->behavior->beforeSave($event, $entity, $options);

        $this->assertSame(5, $entity->get('organization_id'));
    }

    public function testBeforeSaveSkipsWhenContextNotSet(): void
    {
        $entity = new Entity();
        $entity->setNew(true);

        $event = new Event('Model.beforeSave');
        $options = new ArrayObject();

        $this->behavior->beforeSave($event, $entity, $options);

        $this->assertFalse($entity->has('organization_id'));
    }

    public function testBeforeSavePreventsCrossTenantUpdate(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity(['organization_id' => 99]);
        $entity->setNew(false);

        $event = new Event('Model.beforeSave');
        $options = new ArrayObject();

        $this->behavior->beforeSave($event, $entity, $options);

        $this->assertTrue($event->isStopped());
    }

    public function testBeforeSaveAllowsSameTenantUpdate(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity(['organization_id' => 5]);
        $entity->setNew(false);

        $event = new Event('Model.beforeSave');
        $options = new ArrayObject();

        $this->behavior->beforeSave($event, $entity, $options);

        $this->assertFalse($event->isStopped());
    }

    // --- beforeDelete tests ---

    public function testBeforeDeletePreventsCrossTenantDelete(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity(['organization_id' => 99]);

        $event = new Event('Model.beforeDelete');
        $options = new ArrayObject();

        $this->behavior->beforeDelete($event, $entity, $options);

        $this->assertTrue($event->isStopped());
    }

    public function testBeforeDeleteAllowsSameTenantDelete(): void
    {
        TenantContext::setCurrentOrgId(5);

        $entity = new Entity(['organization_id' => 5]);

        $event = new Event('Model.beforeDelete');
        $options = new ArrayObject();

        $this->behavior->beforeDelete($event, $entity, $options);

        $this->assertFalse($event->isStopped());
    }

    public function testBeforeDeleteSkipsWhenContextNotSet(): void
    {
        $entity = new Entity(['organization_id' => 99]);

        $event = new Event('Model.beforeDelete');
        $options = new ArrayObject();

        $this->behavior->beforeDelete($event, $entity, $options);

        $this->assertFalse($event->isStopped());
    }
}
