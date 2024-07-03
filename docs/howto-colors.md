---
title: HOWTO Change Metric Explorer Colors
---

The color palette available to style chart data in Metric Explorer can be edited
in the JSON file `colors1.json` (`/etc/xdmod/colors1.json` if you installed the RPM or
`PREFIX/etc/colors1.json` if you did a manual install).

The default version of `colors1.json` (and what is used as a fallback palette if `colors1.json`
is missing or malformed) is shown below

```json
[
	[
		["FFFFFF"], ["1199FF"], ["DB4230"], ["4E665D"], ["F4A221"], ["66FF00"], ["33ABAB"], ["A88D95"], 
		["789ABC"], ["FF99CC"], ["00CCFF"], ["FFBC71"], ["A57E81"], ["8D4DFF"], ["FF6666"], ["CC99FF"],
		["2F7ED8"], ["0D233A"], ["8BBC21"], ["910000"], ["1AADCE"], ["492970"], ["F28F43"], ["77A1E5"], 
		["3366FF"], ["FF6600"], ["808000"], ["CC99FF"], ["008080"], ["CC6600"], ["9999FF"], ["99FF99"],
		["969696"], ["FF00FF"], ["FFCC00"], ["666699"], ["00FFFF"], ["00CCFF"], ["993366"], ["3AAAAA"], 
		["C0C0C0"], ["FF99CC"], ["FFCC99"], ["CCFFCC"], ["CCFFFF"], ["99CCFF"], ["339966"], ["FF9966"],
		["69BBED"], ["33FF33"], ["6666FF"], ["FF66FF"], ["99ABAB"], ["AB8722"], ["AB6565"], ["990099"],
		["999900"], ["CC3300"], ["669999"], ["993333"], ["339966"], ["C42525"], ["A6C96A"], ["111111"]
	]
]
```

To change the colors available, simply edit the hexadecimal codes for any of the swatches in the palette.
To add a new color or set of colors, make sure to keep the JSON array rectangular with the same width.

As an example, to add the colors `123456` and `654321` you'd need to pad the table with other colors.
In this case, they're padded with white, which Open XDMoD skips when showing charts.

```json
[
	[
		["FFFFFF"], ["1199FF"], ["DB4230"], ["4E665D"], ["F4A221"], ["66FF00"], ["33ABAB"], ["A88D95"], 
		["789ABC"], ["FF99CC"], ["00CCFF"], ["FFBC71"], ["A57E81"], ["8D4DFF"], ["FF6666"], ["CC99FF"],
		["2F7ED8"], ["0D233A"], ["8BBC21"], ["910000"], ["1AADCE"], ["492970"], ["F28F43"], ["77A1E5"], 
		["3366FF"], ["FF6600"], ["808000"], ["CC99FF"], ["008080"], ["CC6600"], ["9999FF"], ["99FF99"],
		["969696"], ["FF00FF"], ["FFCC00"], ["666699"], ["00FFFF"], ["00CCFF"], ["993366"], ["3AAAAA"], 
		["C0C0C0"], ["FF99CC"], ["FFCC99"], ["CCFFCC"], ["CCFFFF"], ["99CCFF"], ["339966"], ["FF9966"],
		["69BBED"], ["33FF33"], ["6666FF"], ["FF66FF"], ["99ABAB"], ["AB8722"], ["AB6565"], ["990099"],
		["999900"], ["CC3300"], ["669999"], ["993333"], ["339966"], ["C42525"], ["A6C96A"], ["111111"],
		["123456"], ["654321"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"]
	]
]
```

Charts in Open XDMoD will go sequentially through this list when deciding what colors to color grouped sets within
one dataset on a chart. As such, you can create a gradient by defining it sequentially in this list. The
following JSON list adds a 8-element "snapshot" of [the Viridis color gradient](https://bids.github.io/colormap/).

```json
[
	[
		["FFFFFF"], ["1199FF"], ["DB4230"], ["4E665D"], ["F4A221"], ["66FF00"], ["33ABAB"], ["A88D95"], 
		["789ABC"], ["FF99CC"], ["00CCFF"], ["FFBC71"], ["A57E81"], ["8D4DFF"], ["FF6666"], ["CC99FF"],
		["2F7ED8"], ["0D233A"], ["8BBC21"], ["910000"], ["1AADCE"], ["492970"], ["F28F43"], ["77A1E5"], 
		["3366FF"], ["FF6600"], ["808000"], ["CC99FF"], ["008080"], ["CC6600"], ["9999FF"], ["99FF99"],
		["969696"], ["FF00FF"], ["FFCC00"], ["666699"], ["00FFFF"], ["00CCFF"], ["993366"], ["3AAAAA"], 
		["C0C0C0"], ["FF99CC"], ["FFCC99"], ["CCFFCC"], ["CCFFFF"], ["99CCFF"], ["339966"], ["FF9966"],
		["69BBED"], ["33FF33"], ["6666FF"], ["FF66FF"], ["99ABAB"], ["AB8722"], ["AB6565"], ["990099"],
		["FDE725"], ["A0DA39"], ["4AC16D"], ["1FA187"], ["277F8E"], ["365C8D"], ["46327E"], ["440154"]
	]
]
```

Open XDMOD indexes the colors based on the first instance of the color found in that list,
so any duplicate colors may wreck havoc on group-by coloring.
As an example, selecting the first `123456` in the following
palette would cause the next two groups in that dataset to be colored `AABBCC` and `CCBBAA`.
If I selected the second `123456`, though, the next two groups, would *still* be colored `AABBCC` and `CCBBAA`.

The second palette below shows a workaround for this to add both an 8-element and 10-element Viridis gradient, by
nudging the starting point over by a value of 1 in any of the 3 RGB channels.

```json
[
	[
		["123456"], ["AABBCC"], ["CCBBAA"], ["123456"], ["111111"], ["222222"], ["333333"], ["444444"]
	]
]
```

```json
[
	[
		["FFFFFF"], ["1199FF"], ["DB4230"], ["4E665D"], ["F4A221"], ["66FF00"], ["33ABAB"], ["A88D95"], 
		["789ABC"], ["FF99CC"], ["00CCFF"], ["FFBC71"], ["A57E81"], ["8D4DFF"], ["FF6666"], ["CC99FF"],
		["2F7ED8"], ["0D233A"], ["8BBC21"], ["910000"], ["1AADCE"], ["492970"], ["F28F43"], ["77A1E5"], 
		["3366FF"], ["FF6600"], ["808000"], ["CC99FF"], ["008080"], ["CC6600"], ["9999FF"], ["99FF99"],
		["969696"], ["FF00FF"], ["FFCC00"], ["666699"], ["00FFFF"], ["00CCFF"], ["993366"], ["3AAAAA"], 
		["C0C0C0"], ["FF99CC"], ["FFCC99"], ["CCFFCC"], ["CCFFFF"], ["99CCFF"], ["339966"], ["FF9966"],
		["69BBED"], ["33FF33"], ["6666FF"], ["FF66FF"], ["99ABAB"], ["AB8722"], ["AB6565"], ["990099"],
		["FDE725"], ["A0DA39"], ["4AC16D"], ["1FA187"], ["277F8E"], ["365C8D"], ["46327E"], ["440154"],
		["FDE726"], ["B5DE2B"], ["6ECE58"], ["35B779"], ["1F9E89"], ["26828e"], ["31688E"], ["3E4989"], 
		["482878"], ["440155"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"], ["FFFFFF"]
	]
]
```
