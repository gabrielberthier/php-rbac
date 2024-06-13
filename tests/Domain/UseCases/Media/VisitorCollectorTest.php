<?php

namespace Tests\Domain\UseCases\Media;

use App\Data\Protocols\Media\MediaCollectorInterface;
use App\Data\Protocols\Media\MediaHostInterface;
use App\Data\UseCases\Media\MediaCollectorVisitor;
use App\Domain\Models\Assets\AbstractAsset;
use PHPUnit\Framework\TestCase;

function createAbstractAsset(): AbstractAsset
{
  return new class extends AbstractAsset {
    public function __construct(public string $mediaType = "stub")
    {
    }

    function getPath(): string
    {
      return "path";
    }
  };
}

class MediaHostInterfaceStub implements MediaHostInterface
{
  public function assetInformation(): ?AbstractAsset
  {
    return createAbstractAsset();
  }

  public function accept(MediaCollectorInterface $visitor): void
  {
    $visitor->visit($this);
  }

  public function namedBy(): string
  {
    return "named";
  }

  function jsonSerialize(): mixed
  {
    return [];
  }
}

class VisitorCollectorTest extends TestCase
{
  private MediaCollectorInterface $sut;

  function setUp(): void
  {
    $this->sut = new MediaCollectorVisitor();
  }

  public function testShouldHaveNoElementsInVisitorArraySetForEmptyAbstractAsset()
  {
    $mhi = $this->getMockBuilder(MediaHostInterfaceStub::class)->getMock();
    $mhi->method('assetInformation')->willReturn(null);
    $this->sut->visit($mhi);

    $this->assertEmpty($this->sut->collect());
  }

  public function testShouldHaveOneElementWhenAssetIsPresent()
  {
    $mhi = $this->getMockBuilder(MediaHostInterfaceStub::class)->getMock();

    $mhi->method('namedBy')->willReturn("");

    $mhi->method('assetInformation')->willReturn(createAbstractAsset());

    $this->sut->visit($mhi);

    $this->assertEquals(1, count($this->sut->collect()));
    $this->assertEquals($this->sut->collect()[0]->path, "path");
  }

  public function testShouldHaveFiveElementsInCollection()
  {
    $arr = [];
    for ($i = 0; $i < 5; $i++) {
      $arr[] = new MediaHostInterfaceStub();
    }

    foreach ($arr as $el) {
      $this->sut->visit($el);
    }

    $this->assertEquals(5, count($this->sut->collect()));
  }
}
