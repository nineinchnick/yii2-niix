<?php

namespace nineinchnick\niix\tests\unit;

use Yii;

class ModelGeneratorTest extends TestCase
{
    public function testGenerator()
    {
        $generator = Yii::createObject('nineinchnick\niix\generators\model\Generator');
        $generator->tableName = 'composite_fk';
        $files = $generator->generate();
        $this->assertEquals(1, count($files));
        $lines = explode("\n", $files[0]->content);
        $relationLines = [
            "        return \$this->hasOne(OrderItem::className(), ".
            "['order_id' => 'order_id', 'item_id' => 'item_id']);",
        ];
        foreach ($relationLines as $relationLine) {
            $this->assertTrue(in_array($relationLine, $lines));
        }
        //! @todo describe advanced rules from yii1's niix
    }
}
