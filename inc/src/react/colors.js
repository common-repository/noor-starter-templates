import { __ } from '@wordpress/i18n';

import color_data from '../../../assets/colors.json';
let all = {};
Object.entries(color_data).map((data, index) => {
  let arr = {};
  let colors = [];
  arr['palette'] = data[0];
  arr['title'] = data[0];
  data[1].map((color, i) => {
    colors[i] = color.color;
  });
  arr['colors'] = colors;

  all[index] = arr;
});
// console.log(all);
export const NOOR_PALETTES = all;
