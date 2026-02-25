<?php

namespace Omnik\Core\Api;

interface SplitOrderInterface
{
    /** @var string */
    const SPLIT_ORDER_PARENT_ID = 'split_order_parent_id';

    /** @var string */
    const SPLIT_ORDER_TYPE = 'split_order_type';

    /** @var string */
    const SPLIT_ORDER_TYPE_PARENT = 'parent';

    /** @var string */
    const SPLIT_ORDER_TYPE_CHILD = 'child';

    /** @var string */
    const SPLIT_ORDER_HAS_INTEGRATED = 'has_integrated_omnik';
}
