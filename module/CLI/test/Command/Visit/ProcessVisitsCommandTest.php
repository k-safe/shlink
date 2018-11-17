<?php
declare(strict_types=1);

namespace ShlinkioTest\Shlink\CLI\Command\Visit;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Shlinkio\Shlink\CLI\Command\Visit\ProcessVisitsCommand;
use Shlinkio\Shlink\Common\IpGeolocation\IpApiLocationResolver;
use Shlinkio\Shlink\Core\Entity\ShortUrl;
use Shlinkio\Shlink\Core\Entity\Visit;
use Shlinkio\Shlink\Core\Model\Visitor;
use Shlinkio\Shlink\Core\Service\VisitService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zend\I18n\Translator\Translator;
use function count;

class ProcessVisitsCommandTest extends TestCase
{
    /**
     * @var CommandTester
     */
    private $commandTester;
    /**
     * @var ObjectProphecy
     */
    private $visitService;
    /**
     * @var ObjectProphecy
     */
    private $ipResolver;

    public function setUp()
    {
        $this->visitService = $this->prophesize(VisitService::class);
        $this->ipResolver = $this->prophesize(IpApiLocationResolver::class);

        $command = new ProcessVisitsCommand(
            $this->visitService->reveal(),
            $this->ipResolver->reveal(),
            Translator::factory([])
        );
        $app = new Application();
        $app->add($command);

        $this->commandTester = new CommandTester($command);
    }

    /**
     * @test
     */
    public function allReturnedVisitsIpsAreProcessed()
    {
        $shortUrl = new ShortUrl('');

        $visits = [
            new Visit($shortUrl, new Visitor('', '', '1.2.3.4')),
            new Visit($shortUrl, new Visitor('', '', '4.3.2.1')),
            new Visit($shortUrl, new Visitor('', '', '12.34.56.78')),
        ];
        $this->visitService->getUnlocatedVisits()->willReturn($visits)
                                                 ->shouldBeCalledOnce();

        $this->visitService->locateVisit(Argument::cetera())->shouldBeCalledTimes(count($visits));
        $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([])
                                                             ->shouldBeCalledTimes(count($visits));

        $this->commandTester->execute([
            'command' => 'visit:process',
        ]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Processing IP 1.2.3.0', $output);
        $this->assertContains('Processing IP 4.3.2.0', $output);
        $this->assertContains('Processing IP 12.34.56.0', $output);
    }

    /**
     * @test
     */
    public function localhostAndEmptyAddressIsIgnored()
    {
        $shortUrl = new ShortUrl('');

        $visits = [
            new Visit($shortUrl, new Visitor('', '', '1.2.3.4')),
            new Visit($shortUrl, new Visitor('', '', '4.3.2.1')),
            new Visit($shortUrl, new Visitor('', '', '12.34.56.78')),
            new Visit($shortUrl, new Visitor('', '', '127.0.0.1')),
            new Visit($shortUrl, new Visitor('', '', '127.0.0.1')),
            new Visit($shortUrl, new Visitor('', '', '')),
            new Visit($shortUrl, new Visitor('', '', null)),
        ];
        $this->visitService->getUnlocatedVisits()->willReturn($visits)
            ->shouldBeCalledOnce();

        $this->visitService->locateVisit(Argument::cetera())->shouldBeCalledTimes(count($visits) - 4);
        $this->ipResolver->resolveIpLocation(Argument::any())->willReturn([])
                                                             ->shouldBeCalledTimes(count($visits) - 4);

        $this->commandTester->execute([
            'command' => 'visit:process',
        ], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->commandTester->getDisplay();
        $this->assertContains('Ignored localhost address', $output);
        $this->assertContains('Ignored visit with no IP address', $output);
    }
}
