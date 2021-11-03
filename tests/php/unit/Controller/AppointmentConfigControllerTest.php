<?php

declare(strict_types=1);
/**
 * Calendar App
 *
 * @copyright 2021 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Calendar\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Calendar\Db\AppointmentConfig;
use OCA\Calendar\Exception\ClientException;
use OCA\Calendar\Exception\ServiceException;
use OCA\Calendar\Service\Appointments\AppointmentConfigService;
use OCP\Contacts\IManager;
use OCP\IInitialStateService;
use OCP\IRequest;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class AppointmentConfigControllerTest extends TestCase {

	/** @var string */
	protected $appName;

	/** @var IRequest|MockObject */
	protected $request;

	/** @var IManager|MockObject */
	protected $manager;

	/** @var IUser|MockObject  */
	protected $user;

	/** @var AppointmentConfigService|MockObject */
	protected $service;

	/** @var AppointmentConfigController */
	protected $controller;

	/** @var MockObject|LoggerInterface */
	private $logger;

	protected function setUp():void {
		parent::setUp();

		$this->appName = 'calendar';
		$this->request = $this->createMock(IRequest::class);
		$this->manager = $this->createMock(IManager::class);
		$this->user = $this->createConfiguredMock(IUser::class, [
			'getUID' => 'testuser'
		]);
		$this->service = $this->createMock(AppointmentConfigService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->controller = new AppointmentConfigController(
			$this->appName,
			$this->request,
			$this->service,
			$this->logger,
			$this->user->getUID()
		);
	}

	public function testIndex(): void {
		$appointments = [new AppointmentConfig()];

		$this->service->expects($this->once())
			->method('getAllAppointmentConfigurations')
			->with($this->user->getUID())
			->willReturn($appointments);

		$this->controller->index('user');
	}

	public function testIndexException(): void {
		$this->service->expects($this->once())
			->method('getAllAppointmentConfigurations')
			->with($this->user->getUID())
			->willThrowException(new ServiceException());

		$this->controller->index('user');
	}

	public function testShow():void {
		$appointment = new AppointmentConfig();
		$id = 1;

		$this->service->expects($this->once())
			->method('findByIdAndUser')
			->with($id, $this->user->getUID())
			->willReturn($appointment);
		$response = $this->controller->show($id);

		$this->assertEquals(200, $response->getStatus());
	}

	public function testShowException():void {
		$id = 1;

		$this->service->expects($this->once())
			->method('findByIdAndUser')
			->with($id, $this->user->getUID())
			->willThrowException(new ClientException());

		$response = $this->controller->show($id);

		$this->assertEquals(400, $response->getStatus());
	}

	public function testCreate():void {
		$appointment = new AppointmentConfig();
		$appointment->setName('Test');
		$appointment->setDescription('Test');
		$appointment->setLocation('Test');
		$appointment->setVisibility('PUBLIC');
		$appointment->setTargetCalendarUri('test');
		$appointment->setLength(5);
		$appointment->setIncrement(5);

		$this->service->expects($this->once())
			->method('create')
			->with('Test', 'Test', 'Test', 'PUBLIC', $this->user->getUID(), 'test', null, 5, 5, 0 , 0, 0, null, null, null)
			->willReturn($appointment);

		$response = $this->controller->create(
			'Test',
			'Test',
			'Test',
			'PUBLIC',
			'test',
			null,
			5,
			5
		);

		$this->assertEquals(200, $response->getStatus());
	}

	public function testCreateException():void {
		$appointment = new AppointmentConfig();
		$appointment->setName('Test');
		$appointment->setDescription('Test');
		$appointment->setLocation('Test');
		$appointment->setVisibility('PUBLIC');
		$appointment->setTargetCalendarUri('test');
		$appointment->setLength(5);
		$appointment->setIncrement(5);

		$this->service->expects($this->once())
			->method('create')
			->with('Test', 'Test', 'Test', 'PUBLIC', $this->user->getUID(), 'test', null, 5, 5, 0,0, 0, null, null, null)
			->willThrowException(new ServiceException());

		$response = $this->controller->create(
			'Test',
			'Test',
			'Test',
			'PUBLIC',
			'test',
			null,
			5,
			5
		);

		$this->assertEquals(500, $response->getStatus());
	}

	public function testUpdate():void {
		$appointment = new AppointmentConfig();
		$appointment->setId(1);
		$appointment->setName('Test');
		$appointment->setDescription('Test');
		$appointment->setLocation('Test');
		$appointment->setVisibility('PUBLIC');
		$appointment->setTargetCalendarUri('test');
		$appointment->setLength(5);
		$appointment->setIncrement(5);

		$this->service->expects($this->once())
			->method('findByIdAndUser')
			->with($appointment->getId(), $this->user->getUID())
			->willReturn($appointment);

		$this->service->expects($this->once())
			->method('update')
			->with($appointment)
			->willReturn($appointment);

		$response = $this->controller->update(
			1,
			'Test',
			'Test',
			'Test',
			'PUBLIC',
			'test',
			null,
			5,
			5
		);

		$this->assertEquals(200, $response->getStatus());
	}

	public function testUpdateNotFound():void {
		$appointment = new AppointmentConfig();
		$appointment->setId(1);
		$appointment->setName('Test');
		$appointment->setDescription('Test');
		$appointment->setLocation('Test');
		$appointment->setVisibility('PUBLIC');
		$appointment->setTargetCalendarUri('test');
		$appointment->setLength(5);
		$appointment->setIncrement(5);

		$this->service->expects($this->once())
			->method('findByIdAndUser')
			->willThrowException(new ClientException());

		$response = $this->controller->update(
			1,
			'Test',
			'Test',
			'Test',
			'PUBLIC',
			'test',
			null,
			5,
			5
		);

		$this->assertEquals(500, $response->getStatus());
	}

	public function testUpdateDBException():void {
		$appointment = new AppointmentConfig();
		$appointment->setId(1);
		$appointment->setName('Test');
		$appointment->setDescription('Test');
		$appointment->setLocation('Test');
		$appointment->setVisibility('PUBLIC');
		$appointment->setTargetCalendarUri('test');
		$appointment->setLength(5);
		$appointment->setIncrement(5);

		$this->service->expects($this->once())
			->method('update')
			->willThrowException(new ServiceException());

		$response = $this->controller->update(
			1,
			'Test',
			'Test',
			'Test',
			'PUBLIC',
			'test',
			null,
			5,
			5
		);

		$this->assertEquals(403, $response->getStatus());
	}
}