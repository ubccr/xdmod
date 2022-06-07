import yaml
from yaml.loader import SafeLoader
from datetime import date

last_mod = date.today()

def get_priority(url):
  if url[-1]=='/':
    return '1.0'
  return '0.5'

with open('_config.yml') as config:
  config_data = yaml.load(config, Loader=SafeLoader)
  latest_version = config_data['latest_version'].replace('.','_')

  with open('sitemap.xml','w') as sitemap:
    sitemap.write('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n')
    with open(f'_data/v{latest_version}toc.yml') as toc:
      toc_data = yaml.load(toc, Loader=SafeLoader)
      for title in toc_data['toc']:
        for page in title['subfolderitems']:
          if page['url'][0]=='/':
            sitemap.write(f'<url>\n<loc>https://open.xdmod.org{page["url"]}</loc>\n<lastmod>{last_mod}</lastmod>\n<changefreq>monthly</changefreq>\n<priority>{get_priority(page["url"])}</priority>\n</url>\n')
    sitemap.write('</urlset>')  
