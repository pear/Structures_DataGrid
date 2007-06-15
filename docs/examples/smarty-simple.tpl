
<!-- Show paging links using the custom getPaging function -->
{getPaging prevImg="<<" nextImg=">>" separator=" | " delta="5"}

<p>Showing records {$firstRecord} to {$lastRecord} 
from {$totalRecordsNum}, page {$currentPage} of {$pagesNum}</p>

<table cellspacing="0">
    <!-- Build header -->
    <tr>
        {section name=col loop=$columnSet}
            <th {$columnSet[col].attributes}>
                <!-- Check if the column is sortable -->
                {if $columnSet[col].link != ""}
                    <a href="{$columnSet[col].link}">{$columnSet[col].label}</a>
                {else}
                    {$columnSet[col].label}
                {/if}
            </th>
        {/section}
    </tr>
    
    <!-- Build body -->
    {section name=row loop=$recordSet}
        <tr {if $smarty.section.row.iteration is even}bgcolor="#EEEEEE"{/if}>
            {section name=col loop=$recordSet[row]}
                <td {$columnSet[col].attributes}>{$recordSet[row][col]}</td>
            {/section}
        </tr>
    {/section}
</table>
