{!! $campaign->isSplitTested() && $campaign->splitTestWinner ? $campaign->splitTestWinner->webview_html : $campaign->contentItem->webview_html !!}
