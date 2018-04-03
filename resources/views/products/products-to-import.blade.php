@foreach($categories as $category)
    @foreach($category->products as $product)
        <item>
            <title>{!! $product->title !!}</title>
            <link>
            http://localhost/budgets-wordpress/2018/03/30/{!! strTolower(urlencode($product->title)) !!}/</link>
            <pubDate>Sat, 30 Mar 2018 11:35:15 +0000</pubDate>
            <dc:creator><![CDATA[admin]]></dc:creator>
            <guid isPermaLink="false">http://localhost/budgets-wordpress/?p={!! $lastPostId !!}</guid>
            <description></description>
            <content:encoded><![CDATA[description <a
                        href="http://localhost/budgets-wordpress/wp-admin/edit.php?category_name={!! $category['parent'] !!}">{!! $category['name'] !!}</a>]]>
            </content:encoded>
            <excerpt:encoded><![CDATA[]]></excerpt:encoded>
            <wp:post_id>{!! $lastPostId !!}</wp:post_id>
            <wp:post_date><![CDATA[2018-03-30 11:35:15]]></wp:post_date>
            <wp:post_date_gmt><![CDATA[2018-03-30 11:35:15]]></wp:post_date_gmt>
            <wp:comment_status><![CDATA[open]]></wp:comment_status>
            <wp:ping_status><![CDATA[open]]></wp:ping_status>
            <wp:post_name><![CDATA[{!! strToLower(urlencode($product->title)) !!}]]></wp:post_name>
            <wp:status><![CDATA[publish]]></wp:status>
            <wp:post_parent>0</wp:post_parent>
            <wp:menu_order>0</wp:menu_order>
            <wp:post_type><![CDATA[post]]></wp:post_type>
            <wp:post_password><![CDATA[]]></wp:post_password>
            <wp:is_sticky>0</wp:is_sticky>
            <category domain="category" nicename="{!! strToLower(urlencode($category['name'])) !!}">
                <![CDATA[{!! $category['name'] !!}]]>
            </category>
            <wp:postmeta>
                <wp:meta_key><![CDATA[product_id]]></wp:meta_key>
                <wp:meta_value><![CDATA[{!! $product->id !!}]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[colors]]></wp:meta_key>
                <wp:meta_value><![CDATA[{!! $product['colors'] !!}]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[size]]></wp:meta_key>
                <wp:meta_value><![CDATA[{!! $product['size'] !!}]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[price]]></wp:meta_key>
                <wp:meta_value><![CDATA[{!! $product['price'] !!}]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[material]]></wp:meta_key>
                <wp:meta_value><![CDATA[{{ $product['material'] }}]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[deleted]]></wp:meta_key>
                <wp:meta_value><![CDATA[0]]></wp:meta_value>
            </wp:postmeta>
            <wp:postmeta>
                <wp:meta_key><![CDATA[image]]></wp:meta_key>
                <wp:meta_value><![CDATA[{!! $product['img'] !!}]]></wp:meta_value>
            </wp:postmeta>
        </item>
        <?php $lastPostId += 1; ?>
    @endforeach
@endforeach
