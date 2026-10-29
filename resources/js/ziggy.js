const Ziggy = {"url":"http:\/\/localhost","port":null,"defaults":{},"routes":{"sesame.login":{"uri":"login","methods":["GET","HEAD"]},"sesame.logout":{"uri":"logout","methods":["POST"]},"home":{"uri":"\/","methods":["GET","HEAD"]},"dashboard":{"uri":"dashboard","methods":["GET","HEAD"]},"profile.edit":{"uri":"settings\/profile","methods":["GET","HEAD"]},"profile.update":{"uri":"settings\/profile","methods":["PATCH"]},"profile.destroy":{"uri":"settings\/profile","methods":["DELETE"]},"appearance":{"uri":"settings\/appearance","methods":["GET","HEAD"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]},"storage.local.upload":{"uri":"storage\/{path}","methods":["PUT"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
