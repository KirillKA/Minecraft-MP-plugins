<?php
  /*
__PocketMine Plugin__
name=Helper
description=Helper
version=0.1
author=WreWolf
class=Helper
apiversion=9
*/
  /*

Small Changelog
===============

0.1:

*/
  class Helper implements Plugin
  {
    private $api, $interval, $server;

    public function __construct(ServerAPI $api, $server = false)
    {
      $this->api      = $api;
      $this->interval = array();
      $this->server   = ServerAPI::request();
    }

    public function init()
    {
      $this->api->addHandler('player.block.touch', array($this, 'handle'), 9);
      $this->api->console->register("tree", "grow Tree", array($this, "commandH"));
      $this->api->console->register("heal", "Heal <nick>", array($this, "commandH"));
      $this->api->console->register("god", "god <nick>", array($this, "commandH"));
      $this->api->console->register("setMapSpawn", "set current position as map spawn", array($this, "commandH"));
      $this->interval = 40;
      $this->api->schedule($this->interval, array($this, "handle"), array(), true, "server.schedule");

    }

    public function __destruct()
    {
    }

    public function commandH($cmd, $params, $issuer, $alias)
    {
      $output = "";
      switch ($cmd) {
        case "god":
          if (!($issuer == "Console") and $params[0] == "") {
            $output .= "Usage: /$cmd <player>\n";
            break;
          }
          if ($params[0] != "") {
            $issuer = $this->api->player->get($params[0]);
            if ($issuer === false) {
              $output .= "player " . $params[0] . " NotFound";
              break;
            }
          }
          $this->api->entity->heal($issuer->eid, 32767);
          break;
        case "heal":
          if (!($issuer instanceof Player) and $params[0] == "") {
            $output .= "Usage: /$cmd <player>\n";
            break;
          }
          if ($params[0] != "") {
            $issuer = $this->api->player->get($params[0]);
            if ($issuer === false) {
              $output .= "player " . $params[0] . " NotFound";
              break;
            }
          }
          $this->api->entity->heal($issuer->eid, 20);
          break;
        case "tree":
          if (!($issuer instanceof Player)) {
            $output .= "Please run this command in-game.\n";
            break;
          }
          switch (strtolower($params[0])) {
            case "redwood":
            case 1:
              $meta = 1;
              break;
            case "brich":
            case 2:
              $meta = 2;
              break;
            case "tree":
            case 0:
              $meta = 0;
              break;
            case "r":
              $meta = (int)rand(0,2);
              break;
            default:
              $output .= "Usage: /$cmd <tree|brich|redwood>\n";
              break 2;
          }
          TreeObject::growTree($issuer->level, new Vector3 (((int)$issuer->entity->x), ((int)$issuer->entity->y), ((int)$issuer->entity->z)), new Random(), $meta);
          $output .= "tree " . strtolower($params[0]) . " spawned";
          break;
        case "setMapSpawn":
          $this->api->level->getDefault()->setSpawn(new Vector3 (((int)$issuer->entity->x), ((int)$issuer->entity->y), ((int)$issuer->entity->z)));
          break;
      }
      return $output;
    }

    public function handle($data, $event)
    {
      switch ($event) {
        case "server.schedule":
          $level   = $this->api->level->getDefault();
          $signs   = array();
          $signs[] = new Position(127, 64, 127, $level);
          $signs[] = new Position(128, 64, 127, $level);
          $signs[] = new Position(127, 63, 127, $level);
          $signs[] = new Position(128, 63, 127, $level);
          $signs[] = new Position(127, 65, 127, $level);
          $signs[] = new Position(128, 65, 127, $level);
//          $signs[] = new Position(93, 71, 110, $level);
//          $signs[] = new Position(93, 71, 111, $level);
//          $signs[] = new Position(93, 71, 112, $level);
//          $signs[] = new Position(93, 71, 110, $level);
//          $signs[] = new Position(93, 71, 111, $level);
//          $signs[] = new Position(93, 71, 112, $level);

          $info    = $this->server->debugInfo();
          $users   = array();
          $users[] = "Online: " . count($this->api->player->getAll());
          $users[] = "TPS: " . $info["tps"];
          $users[] = "Memory: " . $info["memory_usage"];
          $users[] = "Peak: " . $info["memory_peak_usage"];
          foreach ($this->server->clients as $c) {
            $users[] = $c->username;
          }
          foreach ($signs as $k => $signP) {
            $block = new WallSignBlock();
            $level->setBlock($signP, $block);
            $sign = $this->api->tile->get($signP);

            if (!(($sign instanceof Tile) && $sign->class === TILE_SIGN)) {
              $block = new WallSignBlock();
              $level->setBlock($signP, $block);

              //$this->server->api->dhandle("player.block.place", array("player" => "Console", "block" => $block, "target" => $signP, "item" => WALL_SIGN));

              $sign                  = $this->server->api->tile->addSign(
                $level,
                $signP->x,
                $signP->y,
                $signP->z,
                array("", "", "", "")
              );
              $sign->data["creator"] = "Console";
            } else {
              $sign->data['Text1'] = array_shift($users);
              $sign->data['Text2'] = array_shift($users);
              $sign->data['Text3'] = array_shift($users);
              $sign->data['Text4'] = array_shift($users);
            }
            $this->api->tile->spawnToAll($sign);
          }
          break;
      }
    }
  }
