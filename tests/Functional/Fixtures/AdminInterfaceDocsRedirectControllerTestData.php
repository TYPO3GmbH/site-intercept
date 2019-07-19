<?php

namespace App\Tests\Functional\Fixtures;

use App\Entity\DocsServerRedirect;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class AdminInterfaceDocsRedirectControllerTestData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $redirect = (new DocsServerRedirect())
            ->setId(1)
            ->setSource('/p/vendor/packageOld/1.0/Foo.html')
            ->setTarget('/p/vendor/packageNew/1.0/Foo.html');
        $manager->persist($redirect);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }
}
